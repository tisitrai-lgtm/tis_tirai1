<?php
// ============================================================
// yes_Chef — includes/chef_tag.php
// ระบบสุ่ม Chef Tag (เลข 4 หลัก) ที่ไม่ซ้ำกับในฐานข้อมูล
// ============================================================

require_once __DIR__ . '/../config/database.php';

/**
 * สุ่มเลข 4 หลัก (0000–9999) ที่ยังไม่มีใครใช้ร่วมกับ nickname เดียวกัน
 * รองรับ nickname ซ้ำได้สูงสุด 10,000 คน
 *
 * @param  string $nickname  ชื่อเล่นที่ผู้ใช้ต้องการ
 * @return string            เลข 4 หลัก เช่น "0042"
 * @throws RuntimeException  ถ้าเต็มหมดแล้ว (ควรแทบไม่เกิด)
 */
function generateChefTag(string $nickname): string {
    $db = getDB();

    // ดึง tag ที่ใช้ไปแล้วสำหรับ nickname นี้ครั้งเดียว
    $stmt = $db->prepare("SELECT chef_tag FROM users WHERE nickname = :nick");
    $stmt->execute([':nick' => $nickname]);
    $usedTags = array_column($stmt->fetchAll(), 'chef_tag');
    $usedSet  = array_flip($usedTags); // O(1) lookup

    $maxTries = 50;
    for ($i = 0; $i < $maxTries; $i++) {
        $tag = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        if (!isset($usedSet[$tag])) {
            return $tag;
        }
    }

    // Fallback: สแกนหา tag ที่ว่างอย่างเป็นระบบ
    $allTags = range(0, 9999);
    shuffle($allTags);
    foreach ($allTags as $n) {
        $tag = str_pad((string)$n, 4, '0', STR_PAD_LEFT);
        if (!isset($usedSet[$tag])) {
            return $tag;
        }
    }

    throw new RuntimeException("ชื่อเล่น '{$nickname}' ถูกใช้ครบ 10,000 คนแล้ว กรุณาเลือกชื่อเล่นใหม่");
}

/**
 * ตรวจสอบว่า nickname+tag คู่นี้มีในระบบแล้วหรือเปล่า
 */
function isDisplayNameTaken(string $nickname, string $tag): bool {
    $db   = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE nickname = :nick AND chef_tag = :tag");
    $stmt->execute([':nick' => $nickname, ':tag' => $tag]);
    return (int)$stmt->fetchColumn() > 0;
}

/**
 * Sanitize nickname: ตัด whitespace หัวท้าย, จำกัด 40 ตัวอักษร, กรอง tag syntax
 */
function sanitizeNickname(string $raw): string {
    $clean = trim($raw);
    $clean = preg_replace('/#\d{1,4}$/', '', $clean); // ป้องกันผู้ใช้พิมพ์ # เอง
    $clean = mb_substr($clean, 0, 40);
    return $clean;
}
