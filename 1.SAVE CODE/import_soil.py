import pandas as pd
import mysql.connector

# 1. โหลดข้อมูลจากไฟล์ Excel
excel_path = "เอ้-ประมาณตันรายแปลง ณ วันที่ 1-25 สค 69 (รายแปลง - Copy.xlsx"
df = pd.read_excel(excel_path, sheet_name='ปริ้นรายแปลง')

# 2. เชื่อมต่อฐานข้อมูล MySQL
db = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="sugarcane_db"
)
cursor = db.cursor()

success_count = 0

# 3. วนลูปอ่านข้อมูลทีละแถวเพื่อ Insert ลงตาราง soil_data
for index, row in df.iterrows():
    # ดึงข้อมูลและจัดการช่องว่าง
    production_year = str(row['ปี']).strip() if pd.notna(row['ปี']) else ''
    agency = str(row['หน่วย']).strip() if pd.notna(row['หน่วย']) else ''
    
    # แปลงเลขสัญญาเป็นข้อความ และตัด .0 ท้ายสุดออก (กรณีที่ Excel มองเป็นตัวเลขทศนิยม)
    contract_number = str(row['เลขสัญญา']).strip() if pd.notna(row['เลขสัญญา']) else ''
    if contract_number.endswith('.0'):
        contract_number = contract_number[:-2]
        
    quota = str(row['โค้วต้า']).strip() if pd.notna(row['โค้วต้า']) else ''
    plot_id = str(row['แปลง ID']).strip() if pd.notna(row['แปลง ID']) else ''
    
    rai_area = float(row['พื้นที่']) if pd.notna(row['พื้นที่']) else 0.0
    cane_variety = str(row['พันธุ์อ้อย']).strip() if pd.notna(row['พันธุ์อ้อย']) else ''
    
    # นำชนิดอ้อยและจุดสังเกตมารวมกันเก็บไว้ในฟิลด์ notes
    sugar_type = str(row.get('ชนิดอ้อย', '')).strip()
    landmark = str(row.get('จุดสังเกตุแปลง', '')).strip()
    notes = f"ชนิดอ้อย: {sugar_type} | จุดสังเกต: {landmark}"

    # คำสั่ง SQL สำหรับ Insert ข้อมูล
    sql = """
        INSERT INTO soil_data 
        (production_year, agency, contract_number, quota, plot_id, rai_area, cane_variety, notes) 
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
    """
    values = (production_year, agency, contract_number, quota, plot_id, rai_area, cane_variety, notes)
    
    try:
        cursor.execute(sql, values)
        success_count += 1
    except Exception as e:
        print(f"เกิดข้อผิดพลาดที่แถว {index + 1}: {e}")

# 4. บันทึกข้อมูลและปิดการเชื่อมต่อ
db.commit()
print(f"นำเข้าข้อมูลสำเร็จทั้งหมด {success_count} แถวเรียบร้อยแล้ว!")

cursor.close()
db.close()