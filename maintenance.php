<!doctype html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ระบบกำลังปรับปรุง | TIS WaterSuga</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <style>
    :root {
        --primary-color: #312e81;
        --primary-light: #3b82f6;
        --accent-green: #10b981;
        --glass-bg: rgba(255, 255, 255, 0.9);
    }

    * { box-sizing: border-box; }

    body {
        margin: 0;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Sarabun', 'Inter', sans-serif;
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
        padding: 20px;
    }

    .maintenance-box {
        background: var(--glass-bg);
        backdrop-filter: blur(16px);
        padding: 50px 40px;
        border-radius: 24px;
        box-shadow: 0 15px 40px rgba(31, 38, 135, 0.25);
        max-width: 480px;
        width: 100%;
        text-align: center;
        border: 1px solid rgba(255, 255, 255, 0.6);
    }

    .icon-wrap {
        width: 90px;
        height: 90px;
        margin: 0 auto 24px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        animation: spin 3s linear infinite;
    }

    .icon-wrap i {
        font-size: 2.8rem;
        color: #fff;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    h1 {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary-color);
        margin: 0 0 8px;
    }

    .subtitle-text {
        color: #64748b;
        font-size: 0.9rem;
        font-weight: 500;
        letter-spacing: 0.5px;
        margin-bottom: 20px;
    }

    .gradient-line {
        width: 60px;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
        border-radius: 10px;
        margin: 0 auto 24px auto;
    }

    p.desc {
        color: #334155;
        font-size: 1rem;
        line-height: 1.7;
        margin-bottom: 28px;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(16, 185, 129, 0.1);
        color: var(--accent-green);
        font-weight: 600;
        font-size: 0.85rem;
        padding: 8px 18px;
        border-radius: 999px;
        margin-bottom: 8px;
    }

    .status-pill .dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--accent-green);
        animation: pulse 1.5s ease-in-out infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.3; }
    }

    .footer-note {
        margin-top: 24px;
        font-size: 0.78rem;
        color: #94a3b8;
    }
  </style>
</head>
<body>

  <div class="maintenance-box">
    <div class="icon-wrap">
      <i class='bx bx-cog'></i>
    </div>

    <h1>TIS <span style="color: var(--accent-green);">WaterSuga</span></h1>
    <div class="subtitle-text">ระบบใส่รูปแปลงให้น้ำอ้อย</div>
    <div class="gradient-line"></div>

    <p class="desc">
      ขณะนี้ระบบกำลังอยู่ระหว่างการปรับปรุงเพื่อเพิ่มประสิทธิภาพการใช้งาน<br>
      ขออภัยในความไม่สะดวก กรุณากลับมาใช้งานอีกครั้งในภายหลัง
    </p>

    <div class="status-pill">
      <span class="dot"></span> กำลังปรับปรุงระบบ
    </div>

    <div class="footer-note">
      หากมีข้อสงสัยเร่งด่วน กรุณาติดต่อทีมส่งเสริม / แผนกเทคโนโลยีสารสนเทศ
    </div>
  </div>

</body>
</html>