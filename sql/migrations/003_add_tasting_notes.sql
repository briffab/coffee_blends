-- Lets any family member add their own tasting notes to any blend.
-- Run once on an existing database (a fresh install from schema.sql has it).
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
