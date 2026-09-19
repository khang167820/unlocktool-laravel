-- ============================================================
-- Password Rotation Agent Tables for unlocktool.us
-- Chạy SQL này trong phpMyAdmin để tạo 2 bảng mới
-- ============================================================

CREATE TABLE IF NOT EXISTS `password_rotation_agents` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(80) NOT NULL DEFAULT 'Agent Windows',
    `token_hash` VARCHAR(64) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_seen_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_rotation_jobs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `account_id` BIGINT UNSIGNED NOT NULL,
    `service_type` VARCHAR(50) NOT NULL DEFAULT 'Unlocktool',
    `status` VARCHAR(20) NOT NULL DEFAULT 'queued',
    `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `agent_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `locked_until` TIMESTAMP NULL DEFAULT NULL,
    `last_message` TEXT NULL DEFAULT NULL,
    `started_at` TIMESTAMP NULL DEFAULT NULL,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    INDEX `password_rotation_jobs_status_index` (`status`),
    INDEX `password_rotation_jobs_account_id_index` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
