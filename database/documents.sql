-- Task 10: File & Document Management
-- شغّل الملف ده مرة واحدة في phpMyAdmin على قاعدة voltix_db

USE voltix_db;

-- ملحوظة: مفيش FOREIGN KEY على users.id هنا عمدًا، لأن نوع عمود id في جدول
-- users اختلف حسب إزاي اتعمل في التاسكات الأولانية (INT عادي أو UNSIGNED)،
-- وربط FK بين نوعين مختلفين بيفشل. التحقق من إن الـ user_id حقيقي بيحصل
-- في الكود نفسه (PHP) بدل القيد على مستوى الداتابيز.
CREATE TABLE IF NOT EXISTS documents (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    -- الاسم الفعلي للملف على السيرفر: اسم عشوائي مش الاسم الأصلي، عشان نمنع
    -- تضارب الأسماء ومنع حد يخمّن اسم ملف حد تاني
    stored_name   VARCHAR(255) NOT NULL UNIQUE,
    mime_type     VARCHAR(100) NOT NULL,
    size_bytes    INT UNSIGNED NOT NULL,
    uploaded_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_documents_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
