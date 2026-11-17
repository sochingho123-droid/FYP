<?php 
    session_start()
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <link rel="stylesheet" href='../public/style.css'>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/recorderjs/0.1.0/recorder.js"></script>
    <title>Main</title>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div>
                <img src="C:\Users\cwitstudent\Desktop\jsproject\public\img\icon.png" alt="">
                <span>MMedication</span>
            </div>
        </div>
        <div class="header_search">
            <form action="/search" method="get">
                <button id="start-btn">🎙️ 開始語音搜尋</button>
                <div id="status" class="status">狀態: 閒置</div>
                <input type="text" placehoader="Search" name="search" >
                <button type="submit"><i class="material-icons">search</i></button>
            </form>
        </div>
        
        <div class="header-right">
            
        </div>
            
    </div>
    <div class="mainBody">
        <div class="aside1">
            <div class="sidebar_categories">
            </div>
        </div>
        <div class="products">
            <h1>Are you looking for</h1>
            <div class="product_container">
               
            </div>
        </div>
        <div class="aside2">
            <input type="hidden" id="backend-data" value="<%= ad %>">
        </div>
    </div>
    <script>
        let rec;
        let gumStream;

        function resetToIdle() {
            console.log("語音處理結束，重置為待命狀態...");
        }

        async function initDefaultMic() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ 
                audio: true, 
                video: false 
                });
                console.log("Microphone access granted successfully.");

                gumStream = stream;
                audioContext = new (window.AudioContext || window.webkitAudioContext)({ sampleRate: 16000 });
                const input = audioContext.createMediaStreamSource(stream);


                rec = new Recorder(input, { numChannels: 1 });
                rec.record();

                setTimeout(() => {
                    if (rec) {
                            rec.stop();
                            rec.exportWAV(async (blob) => {
                                await sendVoiceToBackend(blob);
                                if (gumStream) {
                                    gumStream.getTracks().forEach(track => track.stop());
                                }
                            });
                        }
                }, 10000);
                
            } catch (error) {
                console.error("Microphone access denied or error occurred:", error);
            }
        }

       async function sendVoiceToBackend(audioBlob) {
            const formData = new FormData();
            // 將錄好的音訊 Blob 打包成 FormData
            formData.append('audio', audioBlob, 'speech.wav'); 
            console.log(formData)
            console.log(audioBlob)

            try {
                const response = await fetch('http://127.0.0.1:1000/speakinput', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const phrase = result.text.toLowerCase();
                    console.log("後端辨識成功的文字為: " + phrase);

                    if (
                        phrase.includes("要") || 
                        phrase.includes("需要") || 
                        phrase.includes("yes") || 
                        phrase.includes("vision") || 
                        phrase.includes("看") // 擴充：例如「我要看電腦視覺」
                    ) {
                        console.log("口令正確，正在引導跳轉至 Computer Vision 頁面...");
                        
                        // 3. 🎯 在前端執行跳轉，這會直接觸發你圖片中的 app.get("/computevision") 路由！
                        window.location.href = "/computevision"; 
                        
                    } else {
                        console.log("語音內容與跳轉指令不符。");
                        resetToIdle(); // 沒對中關鍵字，重設錄音狀態讓使用者再試一次
                    }
                } else {
                    console.log("辨識失敗: " + result.error);
                    resetToIdle();
                }
            } catch (error) {
                console.error("網路請求失敗:", error);
                resetToIdle();
            }
        }


        async function handleUploadAndSpeech(event) {
            if (event) event.preventDefault();
              try {
                    const dataEl = document.getElementById('backend-data');
                    if (!dataEl) {
                        console.error("未找到 id 为 backend-data 的元素");
                        return;
                    }

                    const rawBase64 = dataEl.value.trim();
                    if (!rawBase64) {
                        console.warn("输入框内没有检测到音频数据");
                        return;
                    }
                    const binaryString = window.atob(rawBase64);
                    const len = binaryString.length;
                    const bytes = new Uint8Array(len);
                    for (let i = 0; i < len; i++) {
                        bytes[i] = binaryString.charCodeAt(i);
                    }
                    const audioBlob = new Blob([bytes.buffer]);
                    console.log(audioBlob)

                    const audioUrl = URL.createObjectURL(audioBlob);
                    const audio = new Audio(audioUrl);

                    audio.play().then(() => {
                        console.log("音频自适应解析成功，已开始自动播放！");
                    }).catch(e => {
                        console.log("已被浏览器自动播放安全机制拦截，正在监听您的首次点击动作解锁...");
                        
                        // 只要用户在页面任意地方点一下，声音立刻通过自适应内核破冰播放
                        document.addEventListener('click', () => {
                            audio.play().then(() => {
                                console.log("用户点击成功，音频开始播放！");
                            }).catch(err => {
                                console.error("【极其罕见】格式全自动兼容依然失败，请确认您后端调用的 TTS 接口返回的到底是什么音频格式？", err);
                            });
                        }, { once: true });
                    });


              } catch (error) {
                console.error("前端解析并播放音频发生异常:", error);
            }
        }
        window.addEventListener("DOMContentLoaded", async () => {
            try {
                // 先執行你的播放/處理邏輯
                await handleUploadAndSpeech();
                console.log("前置語音與資料處理完成，現在啟動麥克風...");
                await initDefaultMic();
                
            } catch (err) {
                console.error("初始化流程發生錯誤:", err);
            }
        });
    </script>
</body>
</html>