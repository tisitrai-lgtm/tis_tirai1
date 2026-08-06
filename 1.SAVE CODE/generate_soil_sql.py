import pandas as pd

# โหลดไฟล์ Excel (แถวแรกเป็นหัวคอลัมน์)
excel_path = "เอ้-ประมาณตันรายแปลง ณ วันที่ 1-25 สค 69 (รายแปลง - Copy.xlsx"
df = pd.read_excel(excel_path, header=0)

# สร้างไฟล์ SQL สำหรับตาราง soil_data
sql_filename = "import_soil_data.sql"

with open(sql_filename, "w", encoding="utf-8") as f:
    f.write("-- SQL Import Script for Soil and Field Data\n")
    f.write("SET NAMES utf8mb4;\n\n")
    
    batch_size = 500
    for i in range(0, len(df), batch_size):
        batch = df.iloc[i:i+batch_size]
        values = []
        
        for _, row in batch.iterrows():
            def val(x):
                if pd.isna(x):
                    return "NULL"
                s = str(x).strip()
                if not s or s.lower() == 'nan':
                    return "NULL"
                
                # จัดการตัด .0 ออกจากเลขสัญญาอัตโนมัติ
                if s.endswith('.0'):
                    s = s[:-2]
                    
                escaped = s.replace("'", "\\'")
                return f"'{escaped}'"
            
            prod_year = val(row['ปี'])
            agency = val(row['หน่วย'])
            contract_no = val(row['เลขสัญญา'])
            quota = val(row['โค้วต้า'])
            p_id = val(row['แปลง ID'])
            rai_area = val(row['พื้นที่'])
            
            soil_type = "NULL"
            soil_img = "NULL"
            soil_prep_details = "NULL"
            soil_prep_img = "NULL"
            
            cane_variety = val(row['พันธุ์อ้อย'])
            cane_variety_img = "NULL"
            
            planting_details = "NULL"
            planting_img = "NULL"
            watering_details = "NULL"
            watering_img = "NULL"
            
            germination_percentage = "NULL"
            germination_img = "NULL" # ค่าของตัวแปร
            
            suga_type_val = str(row.get('ชนิดอ้อย', '')).strip()
            landmark_val = str(row.get('จุดสังเกตุแปลง', '')).strip()
            combined_notes = f"ชนิดอ้อย: {suga_type_val} | จุดสังเกต: {landmark_val}"
            notes = val(combined_notes) if (suga_type_val or landmark_val) else "NULL"
            
            v_str = f"({prod_year}, {agency}, {contract_no}, {quota}, {p_id}, {rai_area}, {soil_type}, {soil_img}, {soil_prep_details}, {soil_prep_img}, {cane_variety}, {cane_variety_img}, {planting_details}, {planting_img}, {watering_details}, {watering_img}, {germination_percentage}, {germination_img}, {notes})"
            values.append(v_str)
            
        # ⚠️ แก้ไขตรง germination_img ให้เป็น germination_image ให้ตรงกับโครงสร้างจริงบน Server
        f.write("REPLACE INTO soil_data ("
                "production_year, agency, contract_number, quota, plot_id, rai_area, "
                "soil_type, soil_image, soil_preparation_details, soil_preparation_image, "
                "cane_variety, cane_variety_image, planting_details, planting_image, "
                "watering_details, watering_image, germination_percentage, germination_image, notes"
                ") VALUES\n")
        f.write(",\n".join(values) + ";\n\n")

print(f"สร้างไฟล์ SQL สำเร็จแล้ว: {sql_filename}")