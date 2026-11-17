import sys
import os
from ultralytics import YOLO
import cv2
from flask import Flask, request, jsonify

app = Flask(__name__)#flask

home = os.getcwd()
model = YOLO("yolov8n.pt")#yolo model

@app.route("/upload_endpoint", methods=["POST"])
def get_image():
    if 'image' not in request.files:
        return jsonify({"status": "error", "message": "No image key found"}), 400
    

    image = request.files['image']
    img = cv2.imread(image)
    results = model(image , conf = 0.8)


    for result in results:
        boxes = result.boxes
        if len(boxes) > 0:
            for box in boxes:
                x1, y1, x2, y2 = map(int, box.xyxy[0])
                conf = float(box.conf[0])
                obj = model.names[int(box.cls[0])]
                print(f"偵測到物體: {obj} (信心度: {conf:.2f})")
        else:
            print("未偵測到任何物體")

    return jsonify({
        "status": "success"
    })

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=7000)