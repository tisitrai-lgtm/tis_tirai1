-- ============================================================
-- yes_Chef - Database Schema
-- MySQL 8.0+
-- ============================================================

CREATE DATABASE IF NOT EXISTS yes_chef
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE yes_chef;

-- ------------------------------------------------------------
-- 1. Users (เชฟทุกคนในระบบ)
-- ------------------------------------------------------------
CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  google_id     VARCHAR(128)   NOT NULL UNIQUE,
  email         VARCHAR(255)   NOT NULL UNIQUE,
  nickname      VARCHAR(80)    NOT NULL,              -- "เชฟสมชาย"
  chef_tag      CHAR(4)        NOT NULL,              -- "1122"  (4 หลัก)
  display_name  VARCHAR(100)   GENERATED ALWAYS AS (CONCAT(nickname,'#',chef_tag)) VIRTUAL,
  avatar_url    VARCHAR(500)   DEFAULT NULL,
  is_setup_done TINYINT(1)     NOT NULL DEFAULT 0,   -- 0=ยังไม่ตั้งชื่อ, 1=ตั้งแล้ว
  created_at    TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_nickname_tag (nickname, chef_tag)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 2. Kitchen Groups (กลุ่มห้องครัว)
-- ------------------------------------------------------------
CREATE TABLE kitchens (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(200)   NOT NULL,
  description   TEXT           DEFAULT NULL,
  owner_id      INT UNSIGNED   NOT NULL,              -- Head Chef
  invite_token  VARCHAR(64)    NOT NULL UNIQUE,       -- token สำหรับลิงก์เชิญ
  created_at    TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_kitchen_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3. Kitchen Members (สมาชิกในแต่ละห้องครัว)
-- ------------------------------------------------------------
CREATE TABLE kitchen_members (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kitchen_id    INT UNSIGNED   NOT NULL,
  user_id       INT UNSIGNED   NOT NULL,
  role          ENUM('head_chef','sous_chef','cook','trainee') NOT NULL DEFAULT 'trainee',
  status        ENUM('pending','active','suspended') NOT NULL DEFAULT 'pending',
  joined_at     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  approved_at   TIMESTAMP      DEFAULT NULL,
  approved_by   INT UNSIGNED   DEFAULT NULL,          -- user_id ของ Head Chef ที่อนุมัติ
  UNIQUE KEY uq_kitchen_user (kitchen_id, user_id),
  CONSTRAINT fk_km_kitchen FOREIGN KEY (kitchen_id) REFERENCES kitchens(id) ON DELETE CASCADE,
  CONSTRAINT fk_km_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
  CONSTRAINT fk_km_approver FOREIGN KEY (approved_by) REFERENCES users(id)  ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 4. Auth Logs (Login / Logout สำเร็จ & ล้มเหลว)
-- ------------------------------------------------------------
CREATE TABLE auth_logs (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED   DEFAULT NULL,          -- NULL ถ้า login ล้มเหลว
  email_attempt VARCHAR(255)   DEFAULT NULL,          -- email ที่พยายาม login
  action        ENUM('login_success','login_failed','logout') NOT NULL,
  ip_address    VARCHAR(45)    NOT NULL,              -- รองรับ IPv6
  user_agent    VARCHAR(500)   DEFAULT NULL,
  note          TEXT           DEFAULT NULL,          -- เหตุผลล้มเหลว เช่น "not gmail"
  created_at    TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_auth_user   (user_id),
  INDEX idx_auth_action (action),
  INDEX idx_auth_time   (created_at)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 5. Profile Change Logs (การแก้ไข Profile)
-- ------------------------------------------------------------
CREATE TABLE profile_change_logs (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED   NOT NULL,
  field_changed VARCHAR(50)    NOT NULL,              -- "nickname" | "avatar_url"
  old_value     TEXT           DEFAULT NULL,
  new_value     TEXT           DEFAULT NULL,
  ip_address    VARCHAR(45)    NOT NULL,
  created_at    TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_pcl_user (user_id),
  CONSTRAINT fk_pcl_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 6. Permission Change Logs (การมอบ/เปลี่ยนสิทธิ์ในครัว)
-- ------------------------------------------------------------
CREATE TABLE permission_logs (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kitchen_id    INT UNSIGNED   NOT NULL,
  actor_id      INT UNSIGNED   NOT NULL,              -- Head Chef ที่กระทำ
  target_id     INT UNSIGNED   NOT NULL,              -- สมาชิกที่ถูกเปลี่ยนสิทธิ์
  action        ENUM('approve','suspend','change_role','remove') NOT NULL,
  old_status    VARCHAR(50)    DEFAULT NULL,
  new_status    VARCHAR(50)    DEFAULT NULL,
  old_role      VARCHAR(50)    DEFAULT NULL,
  new_role      VARCHAR(50)    DEFAULT NULL,
  ip_address    VARCHAR(45)    NOT NULL,
  created_at    TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_pl_kitchen (kitchen_id),
  INDEX idx_pl_actor   (actor_id),
  INDEX idx_pl_target  (target_id),
  CONSTRAINT fk_pl_kitchen FOREIGN KEY (kitchen_id) REFERENCES kitchens(id) ON DELETE CASCADE,
  CONSTRAINT fk_pl_actor   FOREIGN KEY (actor_id)   REFERENCES users(id)    ON DELETE CASCADE,
  CONSTRAINT fk_pl_target  FOREIGN KEY (target_id)  REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 7. Sessions (PHP session store เสริม)
-- ------------------------------------------------------------
CREATE TABLE user_sessions (
  session_id    VARCHAR(128)   PRIMARY KEY,
  user_id       INT UNSIGNED   NOT NULL,
  ip_address    VARCHAR(45)    NOT NULL,
  last_activity TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_sess_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
