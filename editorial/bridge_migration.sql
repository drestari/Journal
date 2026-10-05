-- Run only if these columns/index do not already exist.
-- Check first with:
-- SHOW COLUMNS FROM manuscripts;
-- SHOW INDEX FROM manuscripts WHERE Key_name='uq_source_public_submission_id';

ALTER TABLE manuscripts
ADD COLUMN IF NOT EXISTS source_public_submission_id BIGINT(20) UNSIGNED NULL AFTER id,
ADD COLUMN IF NOT EXISTS corresponding_email VARCHAR(190) NULL AFTER corresponding_author_id;

ALTER TABLE manuscripts
ADD UNIQUE KEY uq_source_public_submission_id (source_public_submission_id);
