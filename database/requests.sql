-- Task 7: Customer Request Management
-- شغّل الملف ده مرة واحدة في phpMyAdmin (تبويب SQL) على قاعدة voltix_db

USE voltix_db;

-- لو جدول inquiries مش موجود أصلاً (تركيب جديد للمشروع)، بننشئه بكل الأعمدة المطلوبة.
CREATE TABLE IF NOT EXISTS inquiries (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(150) NOT NULL,
    email      VARCHAR(190) NOT NULL,
    subject    VARCHAR(200) NOT NULL DEFAULT 'General Inquiry',
    message    TEXT         NOT NULL,
    status     ENUM('new', 'in_progress', 'resolved') NOT NULL DEFAULT 'new',
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- لو الجدول كان موجود بالفعل من قبل (من غير status/updated_at)، بنضيفهم هنا.
-- IF NOT EXISTS بتمنع الخطأ لو شغّلت الملف أكتر من مرة بالغلط.
ALTER TABLE inquiries
    ADD COLUMN IF NOT EXISTS status ENUM('new', 'in_progress', 'resolved')
        NOT NULL DEFAULT 'new' AFTER message;

ALTER TABLE inquiries
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP
        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;
