-- Adds a short description to each blend and an optional 0-5 score to tasting
-- notes. Run once on an existing database (a fresh install from schema.sql
-- already has both columns).
ALTER TABLE blends ADD COLUMN description TEXT NULL AFTER roast_date;
ALTER TABLE tasting_notes ADD COLUMN score TINYINT UNSIGNED NULL AFTER user_id;
