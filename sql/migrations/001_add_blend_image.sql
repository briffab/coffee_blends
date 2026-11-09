-- Adds the chosen image to each blend. Safe to run once on an existing database
-- (a fresh install from schema.sql already has this column).
ALTER TABLE blends ADD COLUMN image_path VARCHAR(255) NULL AFTER milky_notes;
