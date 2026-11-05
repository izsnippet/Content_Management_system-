-- Required tables for the Static Page Manager and global comment lock.
CREATE TABLE IF NOT EXISTS pages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(191) NOT NULL UNIQUE,
    content LONGTEXT NOT NULL,
    status ENUM('published', 'draft') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(191) NOT NULL PRIMARY KEY,
    setting_value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Base64 data URIs are larger than the original image bytes, so a short VARCHAR
-- column cannot hold uploads up to the application's 2 MB upload limit.
ALTER TABLE articles MODIFY featured_image MEDIUMTEXT NULL;

INSERT INTO site_settings (setting_key, setting_value)
VALUES ('comments_enabled', '1')
ON DUPLICATE KEY UPDATE setting_value = setting_value;
