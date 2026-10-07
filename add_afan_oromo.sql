-- Add Afan Oromo (Oromiffa) language to the languages table
-- Run this SQL in phpMyAdmin or MySQL command line

INSERT INTO `languages` (`code`, `name`, `native_name`, `is_rtl`, `is_active`, `created_at`, `updated_at`)
SELECT 'om', 'Afan Oromo', 'Afaan Oromoo', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `languages` WHERE `code` = 'om'
);

-- Verify the insertion
SELECT * FROM `languages` WHERE `code` = 'om';
