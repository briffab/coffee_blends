-- Coffee Blends schema
-- Import with: mysql -u USER -p DBNAME < sql/schema.sql

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    reset_token_hash CHAR(64) NULL,
    reset_token_expires DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS blends (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    roast_date DATE NOT NULL,
    espresso_notes TEXT NULL,
    milky_notes TEXT NULL,
    image_path VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_blends_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_blends_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS blend_beans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    blend_id INT UNSIGNED NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    variety VARCHAR(150) NOT NULL,
    weight_g DECIMAL(6,2) NOT NULL,
    roast_temperature DECIMAL(5,1) NULL,
    roast_time_minutes DECIMAL(5,2) NULL,
    roaster_start_temperature DECIMAL(5,1) NULL,
    ambient_temperature DECIMAL(5,1) NULL,
    cloud_conditions ENUM('sunny','partial','cloudy') NULL,
    roast_level ENUM('light','medium','dark','very_dark','oily','burnt') NOT NULL,
    CONSTRAINT fk_beans_blend FOREIGN KEY (blend_id) REFERENCES blends(id) ON DELETE CASCADE,
    INDEX idx_beans_blend (blend_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tasting_notes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    blend_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    espresso_notes TEXT NULL,
    milky_notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notes_blend FOREIGN KEY (blend_id) REFERENCES blends(id) ON DELETE CASCADE,
    CONSTRAINT fk_notes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_notes_blend (blend_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
