-- Adds admin users for the family user-management pages. Run once on an
-- existing database (a fresh install from schema.sql already has the column).
-- The earliest-created account becomes the first admin; promote or demote
-- others from the Users page afterwards.
ALTER TABLE users ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER password_hash;
UPDATE users SET is_admin = 1 ORDER BY id ASC LIMIT 1;
