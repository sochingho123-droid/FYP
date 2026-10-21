'use strict'
require('dotenv').config();
const express = require('express');
const path = require("path")
const fs = require('fs');
const createImageAnalysisClient = require("@azure-rest/ai-vision-image-analysis").default;
const createClient = require("@azure-rest/ai-inference").default;
const { AzureKeyCredential } = require('@azure/core-auth');
const OpenAI = require("openai")
const SpeechSDK = require('microsoft-cognitiveservices-speech-sdk');
const cors = require('cors');
const multer = require('multer');


const app = express();
const PORT = 1000

app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

const upload = multer();


const endpoint = "https://compuvisionjs.cognitiveservices.azure.com/"
const credential = new AzureKeyCredential("Arm2YuMnyhqwaqrLkolUXQ0ji5logbyvHOsjbE5RO7Rezz7mmwWFJQQJ99CGAC5T7U2XJ3w3AAAFACOG4wCo")

const SPEECH_KEY = "FzK42xGIkqtXzJ9chO8i9MrxKYev9nJyoF1EKxkKgm4LBO408E1MJQQJ99CGAC5T7U2XJ3w3AAAYACOG1AHs"; 
const SPEECH_REGION = "francecentral";


//main speak
app.get("/speech/main",async(req,res)=>{
    try{
        const speechConfig = SpeechSDK.SpeechConfig.fromSubscription(
            SPEECH_KEY, 
            SPEECH_REGION
        );


        speechConfig.speechSynthesisVoiceName = 'zh-HK-HiuGaaiNeural'

        speechConfig.speechSynthesisOutputFormat = SpeechSDK.SpeechSynthesisOutputFormat.Audio16Khz128KBitRateMonoMp3;


        const synthesizer = new SpeechSDK.SpeechSynthesizer(speechConfig, null);

        const textToSpeak = `請上存藥物並登記`;

        synthesizer.speakTextAsync(
        textToSpeak ,
        (result) => {
            if (result.reason === SpeechSDK.ResultReason.SynthesizingAudioCompleted) {
                const audioBuffer = Buffer.from(result.audioData);
                res.writeHead(200, {
                    "Content-Type": "audio/mp3",
                    "Content-Length": audioBuffer.length
                });
                res.end(audioBuffer);
            } else {
                console.error("Speech synthesis failed: ", result.errorDetails);
            }
            synthesizer.close(); // Clean up system resources
        })


    }catch (error) {
        console.error("AI 伺服器內部錯誤:", error);
        res.status(500).json({ error: error.message });
    }
})

//speak
app.post("/speech",async(req,res)=>{
    try{
        const waring = req.body.waring
        const mname = req.body.mname
        const muse = req.body.muse
        const mnotice = req.body.mnotice
        const mdate = req.body.mdate

        const dmy = mdate.split("/")
        let date = dmy[0]
        let month = dmy[1]
        let year = dmy[2]
        console.log(dmy)
        if(year===""){
            year = "今"
        }

        const speechConfig = SpeechSDK.SpeechConfig.fromSubscription(
            SPEECH_KEY, 
            SPEECH_REGION
        );


        speechConfig.speechSynthesisVoiceName = 'zh-HK-HiuGaaiNeural'

        speechConfig.speechSynthesisOutputFormat = SpeechSDK.SpeechSynthesisOutputFormat.Audio16Khz128KBitRateMonoMp3;


        const synthesizer = new SpeechSDK.SpeechSynthesizer(speechConfig, null);

        const textToSpeak = `此藥物需要${waring}        藥物名稱${mname}        藥物使用${muse}        注意事項${mnotice}     此藥物到${year}年${month}月${date}日到期`;

        synthesizer.speakTextAsync(
        textToSpeak ,
        (result) => {
            if (result.reason === SpeechSDK.ResultReason.SynthesizingAudioCompleted) {
                const audioBuffer = Buffer.from(result.audioData);
                res.writeHead(200, {
                    "Content-Type": "audio/mp3",
                    "Content-Length": audioBuffer.length
                });
                res.end(audioBuffer);
            } else {
                console.error("Speech synthesis failed: ", result.errorDetails);
            }
            synthesizer.close(); // Clean up system resources
        })


    }catch (error) {
        console.error("AI 伺服器內部錯誤:", error);
        res.status(500).json({ error: error.message });
    }
})

//speech to text


app.post("/aivision", async (req, res) => {
    try {
        const imgPath = req.body.img;
        if (!imgPath) {
            return res.status(400).json({ error: "未提供圖片路徑" });
        }

        // 使用 path.resolve 確保能正確跨專案資料夾讀取到該圖片
        // 假設你的 jsproject-main 和 jsazure 在同一個父目錄下
        const absolutePath = path.resolve('../FYP-AI_funtion', imgPath);
        
        console.log("AI 正在讀取檔案：", absolutePath);
        const imageBuffer = fs.readFileSync(absolutePath);

        const client = createImageAnalysisClient(endpoint, credential);

        const response = await client.path('/imageanalysis:analyze').post({
            body: imageBuffer,
            queryParameters: {
                'api-version': '2024-02-01',
                'features': ['read']
            },
            contentType: 'application/octet-stream'
        });
        console.log(response.status)
        if (response.status != 200) {
            console.error("Azure 回傳了錯誤狀態碼:", response.status);
            console.error("Azure 完整回傳內容:", JSON.stringify(response.body, null, 2));
            
            // 安全地取得錯誤訊息，防止 undefined 導致當機
            const errorMessage = response.body && response.body.error 
                ? response.body.error.message 
                : "未知錯誤";
                
            throw new Error(`Azure 錯誤: ${errorMessage}`);
        }
        const result = response.body;
        let extractedTexts = {};
        let ty,tx

        if (result && result.readResult && Array.isArray(result.readResult.blocks)) {
            console.log("\n--- Azure OCR 文字擷取結果 ---");
            
            result.readResult.blocks.forEach(block => {
                if (block.lines) {
                    for(let i=0; i< block.lines.length; i++){
                        if(i===0){
                            extractedTexts.waring = block.lines[i].text
                            console.log(extractedTexts.waring)
                            console.log(block.lines[i].boundingPolygon)
                        } else if(i===1){
                            extractedTexts.mnum = block.lines[i].text
                            console.log(extractedTexts.mnum)
                            console.log(block.lines[i].boundingPolygon)
                        } else if(i===2){
                            extractedTexts.mquantity = block.lines[i].text
                            console.log(extractedTexts.mquantity)
                            console.log(block.lines[i].boundingPolygon)
                        } else if(i===3){
                            const nfs =block.lines[i].text.split(" ")
                            extractedTexts.mname = nfs[0]
                            extractedTexts.mform = nfs[1]
                            const snfs = nfs.slice(2)
                            const jnfs = snfs.join(" ")
                            extractedTexts.mstrength = jnfs
                            console.log(extractedTexts.mname)
                            console.log(extractedTexts.mform)
                            console.log(extractedTexts.mstrength)
                            console.log(block.lines[i].boundingPolygon)
                        }else if(i===4){
                            extractedTexts.muse = block.lines[i].text
                            console.log(block.lines[i].boundingPolygon)
                        }else if(i>4){
                            if(!extractedTexts.mnotice){
                                extractedTexts.mnotice = block.lines[i].text
                            }else{
                                if(!ty || !tx){
                                    extractedTexts.mnotice += block.lines[i].text
                                    ty = block.lines[i].boundingPolygon[2].y
                                    tx = block.lines[i].boundingPolygon[0].x
                                }else{
                                    const currentY = block.lines[i].boundingPolygon[1].y;
                                    const currentX = block.lines[i].boundingPolygon[0].x;

                                    const yDiff = (currentY - ty);
                                    const xDiff = (currentX - tx);

                                    if (yDiff < 15 && xDiff < 300){

                                        extractedTexts.mnotice += block.lines[i].text

                                        ty = block.lines[i].boundingPolygon[2].y
                                        tx = block.lines[i].boundingPolygon[0].x

                                        console.log(ty)
                                        console.log(tx)
                                    }
                                    else if(yDiff < 35 && xDiff > 300){
                                        extractedTexts.mdate = block.lines[i].text
                                    }
                                }
                            }
                            console.log(block.lines[i].text)
                            console.log(block.lines[i].boundingPolygon)
                        }
                    }
                }
            });
        }
        console.log(extractedTexts)

        // 3. 🌟 關鍵改動：不要只傳原生的大 json，直接把整理好的文字陣列傳回去
        res.json({ 
            success: true,
            textList: extractedTexts
        });

    } catch (error) {
        console.error("AI 伺服器內部錯誤:", error);
        res.status(500).json({ error: error.message });
    }
});


/*app.get("/open/:key", async(req,res)=>{
    try{
        const keyword = req.params.key
        const openendpoint = "https://opanai4463-resource.services.ai.azure.com/openai/v1"
        const deploymentName ="gpt-5.4"
        const api_key = "EHa1S6jzale1Y0k4IXec3pujArOjPTFT7gbYn7DWAU7zAPgF0MtGJQQJ99CGAC5T7U2XJ3w3AAAAACOGa1Gl"

        const openai = new OpenAI({
            baseURL: openendpoint,
            apiKey: api_key
        })

        const runner = openai.responses
            .stream({
                model:deploymentName,
                input: `"${keyword}". What is this medication typically used to sovle? use one word to describe it.`
            })
            .on('event',(event)=> console.log(event))
            .on('response.output_text.delta', (diff) => process.stdout.write(diff.delta))

        for await (const event of runner){
            console.log('event', event)
        }

        const result = await runner.finalResponse()

        console.log(result)

        res.json(result);
    
    } catch (error) {
        console.error("Foundry 伺服器內部錯誤：", error);
        res.status(500).json({ error: error.message });
    }
})*/

app.listen(PORT,()=>{
    console.log(`Connected on port ${PORT}`)
})