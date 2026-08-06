import pandas as pd

# โหลดไฟล์ Excel (แถวแรกเป็นหัวคอลัมน์)
excel_path = "เอ้-ประมาณตันรายแปลง ณ วันที่ 1-25 สค 69 (รายแปลง - Copy.xlsx"
df = pd.read_excel(excel_path, header=0)

# สร้างไฟล์ SQL
sql_filename = "import_sugarcane_data.sql"

with open(sql_filename, "w", encoding="utf-8") as f:
    f.write("-- SQL Import Script for Sugarcane Data\n")
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
                escaped = s.replace("'", "\\'")
                return f"'{escaped}'"
            
            # ดึงข้อมูลตามโครงสร้างคอลัมน์จริงในไฟล์ Excel
            prod_year = val(row['ปี'])                  # production_year
            agency = val(row['หน่วย'])                  # agency
            emp_num = val(row['นสส'])                   # emp_number (นสส)
            contract_no = val(row['เลขสัญญา'])         # contract_number
            quota = val(row['โค้วต้า'])                  # quota
            p_id = val(row['แปลง ID'])                  # plot_id
            rai_area = val(row['พื้นที่'])               # rai_area
            suga_type = val(row['ชนิดอ้อย'])            # suga_type
            notes = val(row['จุดสังเกตุแปลง'])            # notes
            
            # จัดเรียงชุดข้อมูลให้ตรงกับลำดับคอลัมน์ในตารางของคุณ
            est_ton_1 = "NULL"
            est_ton_2 = "NULL"
            eval_ton_1 = "NULL"
            eval_ton_2 = "NULL"
            rem_1_img_1 = "NULL"
            rem_1_img_2 = "NULL"
            rem_2_img_1 = "NULL"
            rem_2_img_2 = "NULL"
            rem_3_img_1 = "NULL"
            rem_3_img_2 = "NULL"
            
            v_str = f"({p_id}, {prod_year}, {contract_no}, {quota}, {agency}, {suga_type}, {rai_area}, {est_ton_1}, {est_ton_2}, {eval_ton_1}, {eval_ton_2}, {rem_1_img_1}, {rem_1_img_2}, {rem_2_img_1}, {rem_2_img_2}, {rem_3_img_1}, {rem_3_img_2}, {notes}, {emp_num})"
            values.append(v_str)
            
        # ใช้ชื่อตารางจริง cane_plot_data
        f.write("REPLACE INTO cane_plot_data ("
                "plot_id, production_year, contract_number, quota, agency, suga_type, rai_area, "
                "estimate_ton_1, estimate_ton_2, evaluate_ton_1, evaluate_ton_2, "
                "remaining_cane_1_img_1, remaining_cane_1_img_2, remaining_cane_2_img_1, remaining_cane_2_img_2, "
                "remaining_cane_3_img_1, remaining_cane_3_img_2, notes, emp_number"
                ") VALUES\n")
        f.write(",\n".join(values) + ";\n\n")

print(f"สร้างไฟล์ SQL สำเร็จแล้ว: {sql_filename}")