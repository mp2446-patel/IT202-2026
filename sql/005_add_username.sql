-- UCID: mp2446
-- Date: 2026-07-30
-- Adds a safe public username for association lists and public profiles.

ALTER TABLE Users
    ADD COLUMN username VARCHAR(50) NULL AFTER id;

UPDATE Users
SET username = CONCAT('user', id)
WHERE username IS NULL OR username = '';

ALTER TABLE Users
    MODIFY username VARCHAR(50) NOT NULL;

ALTER TABLE Users
    ADD CONSTRAINT uq_users_username UNIQUE (username);