-- Migration script to align persona_modes table with Moodle standards
-- Backup the table first before running this migration

-- Check if the table exists and has the old columns
-- This script converts created_at/updated_at to timecreated

-- Add timecreated column if it doesn't exist
ALTER TABLE mdl_persona_modes 
ADD COLUMN IF NOT EXISTS timecreated BIGINT(10) DEFAULT NULL COMMENT 'Creation/update timestamp (Moodle standard)';

-- Copy data from created_at to timecreated (use most recent timestamp)
UPDATE mdl_persona_modes 
SET timecreated = GREATEST(COALESCE(created_at, 0), COALESCE(updated_at, 0))
WHERE timecreated IS NULL;

-- Make timecreated NOT NULL after data migration
ALTER TABLE mdl_persona_modes 
MODIFY COLUMN timecreated BIGINT(10) NOT NULL COMMENT 'Creation/update timestamp (Moodle standard)';

-- Add index on timecreated for performance
ALTER TABLE mdl_persona_modes 
ADD INDEX IF NOT EXISTS idx_timecreated (timecreated);

-- Optional: Drop old columns after verification
-- Uncomment these lines after verifying the migration is successful
-- ALTER TABLE mdl_persona_modes DROP COLUMN created_at;
-- ALTER TABLE mdl_persona_modes DROP COLUMN updated_at;

-- Verify the changes
-- SHOW COLUMNS FROM mdl_persona_modes;