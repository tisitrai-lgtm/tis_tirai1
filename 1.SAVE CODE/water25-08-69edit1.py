import pandas as pd

excel_path = "แปลงให้น้ำเพิ่ม ครั้งที่ 1 ส่งเพิ่ม.xlsx"
df = pd.read_excel(excel_path, sheet_name="ส่งเพิ่มครั้งที่ 1", header=1)

sql_filename = "overwrite_water_data_69_70.sql"

# ระบุ ID แปลงที่ต้องการข้าม (ยกเว้น)
EXCLUDE_IDS = ["102084"]


def val(x, is_date=False):
    if pd.isna(x):
        return "NULL"
    s = str(x).strip()
    if not s or s.lower() == "nan":
        return "NULL"
    if is_date:
        if " " in s:
            s = s.split(" ")[0]
        return f"'{s}'"
    escaped = s.replace("'", "\\'")
    return f"'{escaped}'"


with open(sql_filename, "w", encoding="utf-8") as f:
    f.write("-- SQL Script: อัปเดตข้อมูลแหล่งน้ำและรอบการให้น้ำ เฉพาะปี 69-70\n")
    f.write("SET NAMES utf8mb4;\n\n")

    count = 0
    for _, row in df.iterrows():
        p_id_raw = str(row.get("ไอดีแปลง", "")).strip()

        # ข้ามแถวที่ไม่มีรหัสแปลง หรือเป็น ID ที่ยกเว้น
        if not p_id_raw or p_id_raw.lower() == "nan" or p_id_raw in EXCLUDE_IDS:
            continue

        p_id = val(p_id_raw)
        yr = val(row.get("ปี"))  # ดึงค่าปี เช่น '69-70'
        water_source = val(row.get("ข้อมูลแหล่งน้ำ"))
        wm1 = val(row.get("วิธีการให้น้ำครั้งที่ 1"))
        wd1 = val(row.get("วันที่ให้น้ำ 1"), is_date=True)
        wm2 = val(row.get("วิธีการให้น้ำครั้งที่ 2"))
        wd2 = val(row.get("วันที่ให้น้ำ 2"), is_date=True)
        wm3 = val(row.get("วิธีการให้น้ำครั้งที่ 3"))
        wd3 = val(row.get("วันที่ให้น้ำ 3"), is_date=True)

        # ล็อกเงื่อนไขทั้ง plot_id และ year_rai
        sql = (
            f"UPDATE image_water SET "
            f"water_source = {water_source}, "
            f"water_method1 = {wm1}, "
            f"water_date1 = {wd1}, "
            f"water_method2 = {wm2}, "
            f"water_date2 = {wd2}, "
            f"water_method3 = {wm3}, "
            f"water_date3 = {wd3} "
            f"WHERE plot_id = {p_id} AND year_rai = {yr};\n"
        )
        f.write(sql)
        count += 1

print(f"สร้างไฟล์ SQL สำเร็จแล้ว: {sql_filename} (จำนวน {count} แปลง)")