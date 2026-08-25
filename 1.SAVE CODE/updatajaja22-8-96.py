import pandas as pd

# 1. โหลดไฟล์ Excel (ระบุ header=1 เนื่องจากหัวตารางอยู่แถวที่ 2)
excel_path = "แปลงให้น้ำเพิ่ม ครั้งที่ 1 ส่งเพิ่ม.xlsx"
df = pd.read_excel(excel_path, sheet_name="ส่งเพิ่มครั้งที่ 1", header=1)

sql_filename = "update_water_data.sql"

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
    f.write("-- SQL Update Script (เพิ่มข้อมูลการให้น้ำครั้งที่ 2 และ 3)\n")
    f.write("SET NAMES utf8mb4;\n\n")

    for _, row in df.iterrows():
        p_id = val(row.get("ไอดีแปลง"))

        # ข้ามแถวที่ไม่มีรหัสแปลง
        if p_id == "NULL":
            continue

        wm2 = val(row.get("วิธีการให้น้ำครั้งที่ 2"))
        wd2 = val(row.get("วันที่ให้น้ำ 2"), is_date=True)
        wm3 = val(row.get("วิธีการให้น้ำครั้งที่ 3"))
        wd3 = val(row.get("วันที่ให้น้ำ 3"), is_date=True)

        # สร้างชุดข้อมูลที่ต้องการ Update
        updates = []
        if wm2 != "NULL":
            updates.append(f"water_method2 = {wm2}")
        if wd2 != "NULL":
            updates.append(f"water_date2 = {wd2}")
        if wm3 != "NULL":
            updates.append(f"water_method3 = {wm3}")
        if wd3 != "NULL":
            updates.append(f"water_date3 = {wd3}")

        # อัปเดตเฉพาะแปลงที่มีข้อมูลรอบ 2 หรือ 3 ส่งมา และในฐานเดิมยังไม่มีการให้น้ำครั้งที่ 2
        if updates:
            set_clause = ", ".join(updates)
            sql = (
                f"UPDATE image_water "
                f"SET {set_clause} "
                f"WHERE plot_id = {p_id} "
                f"AND (water_method2 IS NULL OR water_method2 = '' OR water_date2 IS NULL);\n"
            )
            f.write(sql)

print(f"สร้างไฟล์ SQL สำเร็จแล้ว: {sql_filename}")