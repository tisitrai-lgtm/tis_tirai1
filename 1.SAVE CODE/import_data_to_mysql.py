import pandas as pd

# โหลดไฟล์ Excel และระบุชีทข้อมูล
excel_path = "ฐานให้นำทำใบสมัคร ฐาน ณ 21-7-69.xlsx"
df = pd.read_excel(excel_path, sheet_name="ฐานข้อมูล")

# สร้างไฟล์ SQL ที่ตรงกับโครงสร้างตารางของท่าน
sql_filename = "import_data_to_mysql.sql"

with open(sql_filename, "w", encoding="utf-8") as f:
    f.write("-- SQL Import Script (Support Multiple Years per Plot ID)\n")
    f.write("SET NAMES utf8mb4;\n\n")
    
    batch_size = 500
    for i in range(0, len(df), batch_size):
        batch = df.iloc[i:i+batch_size]
        values = []
        
        for _, row in batch.iterrows():
            def val(x, is_date=False):
                if pd.isna(x):
                    return "NULL"
                s = str(x).strip()
                if not s or s.lower() == 'nan':
                    return "NULL"
                if is_date:
                    if ' ' in s:
                        s = s.split(' ')[0]
                    return f"'{s}'"
                escaped = s.replace("'", "\\'")
                return f"'{escaped}'"
            
            # ดึงข้อมูลให้ตรงกับคอลัมน์ในฐานข้อมูลของคุณ
            p_id = val(row['ไอดีแปลง'])
            citizen_id = val(row['เลขบัตรประชาชน'])
            house_no = val(row['ที่อยู่'])
            sub_district = val(row['ตำบล'])
            district = val(row['อำเภอ'])
            province = val(row['จังหวัด'])
            water_source = val(row['ข้อมูลแหล่งน้ำ'])
            yr = val(row['ปี'])
            emp_id = val(row['รหัส นักส่งเสริม'])
            contract_number = val(row['เลขสัญญา'])
            quota = val(row['ชื่อชาวไร่'])  # โควตา = ชื่อชาวไร่
            area_rai = val(row['พื้นที่ไร่'])
            suga_type = val(row['ชนิดอ้อย'])
            
            wm1 = val(row['วิธีการให้น้ำครั้งที่ 1'])
            wd1 = val(row['วันที่ให้น้ำ 1'], is_date=True)
            wm2 = val(row['วิธีการให้น้ำครั้งที่ 2'])
            wd2 = val(row['วันที่ให้น้ำ 2'], is_date=True)
            wm3 = val(row['วิธีการให้น้ำครั้งที่ 3'])
            wd3 = val(row['วันที่ให้น้ำ 3'], is_date=True)
            
            # จัดเรียงชุดข้อมูลให้ตรงกับลำดับคอลัมน์ในตาราง
            v_str = f"({p_id}, {citizen_id}, {house_no}, {sub_district}, {district}, {province}, {water_source}, {yr}, {emp_id}, {contract_number}, {quota}, {area_rai}, {suga_type}, {wm1}, {wd1}, {wm2}, {wd2}, {wm3}, {wd3}, 'join')"
            values.append(v_str)
            
        # ระบุชื่อคอลัมน์ทั้งหมดตามโครงสร้างตารางจริง
        f.write("REPLACE INTO image_water ("
                "plot_id, citizen_id, house_no, sub_district, district, province, "
                "water_source, year_rai, emp_id, contract_number, quota, area_rai, suga_type, "
                "water_method1, water_date1, water_method2, water_date2, water_method3, water_date3, join_status"
                ") VALUES\n")
        f.write(",\n".join(values) + ";\n\n")

print(f"สร้างไฟล์ SQL สำเร็จแล้ว: {sql_filename}")