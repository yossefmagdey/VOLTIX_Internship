-- Task 6: Company Service Management
-- شغّل الملف ده مرة واحدة في phpMyAdmin (تبويب SQL) على قاعدة voltix_db

USE voltix_db;

CREATE TABLE IF NOT EXISTS services (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    category    VARCHAR(100) NOT NULL DEFAULT 'General',
    title       VARCHAR(150) NOT NULL,
    description TEXT         NOT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- بيانات مبدئية للتجربة (اختياري)
INSERT INTO services (category, title, description) VALUES
('Tracking',    'Live Shipment Tracking',   'Follow every shipment on one timeline across all your carriers and warehouses.'),
('Integration', 'Carrier Integration',      'Connect your carriers and warehouse systems without writing a single line of code.'),
('Support',     'Dedicated Onboarding',     'A named specialist helps your dispatch team get up and running in the first 30 days.');
