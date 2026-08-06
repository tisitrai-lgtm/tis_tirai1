from flask import Flask, request, jsonify
from flask_cors import CORS
import cv2
import numpy as np
from tensorflow.keras.models import load_model # เพิ่มบรรทัดนี้
import os # เพิ่มบรรทัดนี้

app = Flask(__name__)
CORS(app)

# --- โหลด AI Model ล่วงหน้าเมื่อ API เริ่มทำงาน ---
# ตรวจสอบให้แน่ใจว่าไฟล์ sugarcane_trash_model.h5 อยู่ในโฟลเดอร์เดียวกัน
MODEL_PATH = 'sugarcane_trash_model.h5'
IMG_SIZE = (128, 128) # ต้องตรงกับที่ใช้ใน train_model.py

sugarcane_model = None
if os.path.exists(MODEL_PATH):
    sugarcane_model = load_model(MODEL_PATH, compile=False)
    print("AI Model loaded successfully!")
else:
    print(f"Warning: AI Model not found at {MODEL_PATH}. Using HSV-based analysis.")
    # ถ้าไม่มี Model จะใช้ค่า HSV เดิม หรือแจ้งเตือนให้ไป Train ก่อน

@app.route('/analyze', methods=['POST'])
def analyze():
    try:
        if 'image' not in request.files:
            return jsonify({"error": "No image uploaded"}), 400
            
        file = request.files['image']
        img_bytes = np.frombuffer(file.read(), np.uint8)
        img = cv2.imdecode(img_bytes, cv2.IMREAD_COLOR)

        if img is None:
            return jsonify({"error": "Invalid image format"}), 400

        # --- ส่วนวิเคราะห์ใบอ้อยแห้ง (ใช้ AI Model หรือ HSV) ---
        percentage = 0

        if sugarcane_model: # ถ้ามี AI Model โหลดอยู่ ให้ใช้ Model นี้
            # 1. ประมวลผลรูปภาพให้เหมือนตอน Training
            img_resized = cv2.resize(img, IMG_SIZE)
            img_rgb = cv2.cvtColor(img_resized, cv2.COLOR_BGR2RGB)
            img_normalized = img_rgb / 255.0
            img_input = np.expand_dims(img_normalized, axis=0) # เพิ่ม dimension สำหรับ Model

            # 2. ทายผลด้วย AI Model
            predicted_percentage_norm = sugarcane_model.predict(img_input)[0][0]
            percentage = int(round(predicted_percentage_norm * 100)) # แปลงกลับเป็น 0-100%
            print(f"AI Model prediction: {percentage}%")

        else: # ถ้าไม่มี AI Model ให้ใช้การคำนวณแบบ HSV เดิม
            print("Using HSV-based analysis (AI Model not loaded).")
            blurred = cv2.GaussianBlur(img, (5, 5), 0)
            hsv = cv2.cvtColor(blurred, cv2.COLOR_BGR2HSV)
            
            # ค่า HSV ที่เราจูนล่าสุด
            lower_brown = np.array([8, 45, 50])   
            upper_brown = np.array([25, 255, 240]) 
            
            mask = cv2.inRange(hsv, lower_brown, upper_brown)
            trash_pixels = cv2.countNonZero(mask)
            total_pixels = img.shape[0] * img.shape[1]
            percentage = (trash_pixels / total_pixels) * 100
            percentage = int(round(percentage)) 
            print(f"HSV-based analysis: {percentage}%")

        return jsonify({
            "trash_percentage": percentage,
            "status": "success"
        })

    except Exception as e:
        print(f"Error occurred: {str(e)}")
        return jsonify({"error": str(e)}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5050, debug=True)