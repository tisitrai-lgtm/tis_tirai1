import cv2
import numpy as np
import pandas as pd
import mysql.connector
from sklearn.model_selection import train_test_split
from tensorflow.keras.models import Sequential, load_model
from tensorflow.keras.layers import Conv2D, MaxPooling2D, Flatten, Dense, Dropout
from tensorflow.keras.preprocessing.image import ImageDataGenerator
import os

# --- 1. กำหนดค่าการเชื่อมต่อฐานข้อมูล ---
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root', # เปลี่ยนเป็น user ของคุณ
    'password': '', # เปลี่ยนเป็น password ของคุณ (ถ้ามี)
    'database': 'utm_db' # เปลี่ยนเป็นชื่อ database ของคุณ
}

# --- 2. กำหนดขนาดรูปภาพสำหรับ AI (ควรเป็นค่าคงที่) ---
IMG_SIZE = (128, 128) # ลดขนาดรูปภาพเพื่อลดเวลาการ Train และใช้ RAM น้อยลง

# --- 3. ฟังก์ชันสำหรับโหลดและประมวลผลรูปภาพ ---
def load_and_preprocess_image(image_path):
    try:
        # ตรวจสอบว่าไฟล์รูปภาพมีอยู่จริงหรือไม่
        if not os.path.exists(image_path):
            print(f"Warning: Image file not found at {image_path}. Skipping.")
            return None

        img = cv2.imread(image_path)
        if img is None:
            print(f"Warning: Could not read image from {image_path}. Skipping.")
            return None
        
        # ปรับขนาดรูปภาพให้เท่ากันทุกรูป
        img = cv2.resize(img, IMG_SIZE)
        # แปลง BGR เป็น RGB (Keras มักจะใช้ RGB)
        img = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)
        # Normalize ค่าพิกเซลให้อยู่ในช่วง 0-1
        img = img / 255.0
        return img
    except Exception as e:
        print(f"Error processing image {image_path}: {e}. Skipping.")
        return None

# --- 4. ดึงข้อมูลจากฐานข้อมูล ---
def fetch_data_from_db():
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor(dictionary=True) # ดึงข้อมูลเป็น Dictionary
        
        # ดึง image_filename และ trash_percentage
        # เฉพาะแถวที่ image_filename ไม่ใช่ NULL และมีไฟล์รูปภาพอยู่จริง
        # เพื่อใช้เป็นข้อมูลสำหรับสอน AI
        cursor.execute("SELECT image_filename, trash_percentage FROM plots_inspection WHERE image_filename IS NOT NULL AND trash_percentage IS NOT NULL")
        data = cursor.fetchall()
        
        cursor.close()
        conn.close()
        return data
    except mysql.connector.Error as err:
        print(f"Error fetching data from DB: {err}")
        return []

# --- 5. สร้าง Model โครงข่ายประสาทเทียม (CNN) ---
def create_model():
    model = Sequential([
        Conv2D(32, (3, 3), activation='relu', input_shape=(IMG_SIZE[0], IMG_SIZE[1], 3)),
        MaxPooling2D((2, 2)),
        Conv2D(64, (3, 3), activation='relu'),
        MaxPooling2D((2, 2)),
        Conv2D(128, (3, 3), activation='relu'),
        MaxPooling2D((2, 2)),
        Flatten(), # แปลงรูปภาพ 2D เป็น 1D
        Dense(128, activation='relu'),
        Dropout(0.5), # ลดโอกาสเกิด Overfitting
        Dense(1, activation='linear') # Output เป็นตัวเลขเปอร์เซ็นต์ (Regression)
    ])
    # ใช้ Adam optimizer และ Mean Squared Error (MSE) สำหรับ Regression
    model.compile(optimizer='adam', loss='mse', metrics=['mae']) 
    return model

# --- 6. ฟังก์ชันหลักในการ Train Model ---
def train_model():
    print("Fetching data from database...")
    db_data = fetch_data_from_db()

    if not db_data:
        print("No valid data found in the database for training. Please ensure 'image_filename' and 'trash_percentage' are available.")
        return

    images = []
    percentages = []

    # ประมวลผลรูปภาพและเปอร์เซ็นต์
    for row in db_data:
        image_path = row['image_filename']
        # ตรวจสอบว่า image_path ไม่ใช่ URL และเป็นไฟล์ในเครื่อง
        if image_path and not image_path.startswith('http') and os.path.exists(image_path):
            img = load_and_preprocess_image(image_path)
            if img is not None:
                images.append(img)
                percentages.append(row['trash_percentage'] / 100.0) # Normalize เปอร์เซ็นต์เป็น 0-1
        else:
            print(f"Skipping invalid/missing image path: {image_path}")

    if not images:
        print("No valid images found after preprocessing. Aborting training.")
        return

    X = np.array(images)
    y = np.array(percentages)

    print(f"Loaded {len(X)} images for training.")

    # แบ่งข้อมูลเป็น Training และ Validation Set
    X_train, X_val, y_train, y_val = train_test_split(X, y, test_size=0.2, random_state=42)

    # สร้าง Model หรือโหลด Model เก่ามาฝึกต่อ
    model_path = 'sugarcane_trash_model.h5'
    if os.path.exists(model_path):
        print("Loading existing model to continue training...")
        model = load_model(model_path)
    else:
        print("Creating new model...")
        model = create_model()

    # Data Augmentation (ช่วยให้ AI เรียนรู้ได้ดีขึ้นจากข้อมูลน้อย)
    datagen = ImageDataGenerator(
        rotation_range=20,
        width_shift_range=0.2,
        height_shift_range=0.2,
        shear_range=0.2,
        zoom_range=0.2,
        horizontal_flip=True,
        fill_mode='nearest'
    )

    print("Starting model training...")
    history = model.fit(
        datagen.flow(X_train, y_train, batch_size=32),
        epochs=50, # จำนวนรอบการฝึกฝน (อาจจะต้องปรับเพิ่ม/ลด)
        validation_data=(X_val, y_val)
    )

    # บันทึก Model ที่ฝึกแล้ว
    model.save(model_path)
    print(f"Model saved to {model_path}")

    # (Optional) โชว์กราฟการเรียนรู้
    # import matplotlib.pyplot as plt
    # plt.plot(history.history['loss'], label='loss')
    # plt.plot(history.history['val_loss'], label='val_loss')
    # plt.plot(history.history['mae'], label='mae')
    # plt.plot(history.history['val_mae'], label='val_mae')
    # plt.legend()
    # plt.show()


if __name__ == '__main__':
    train_model()