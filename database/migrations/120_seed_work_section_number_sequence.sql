INSERT INTO number_sequences (sequence_key, current_value)
SELECT 'work_sections', COALESCE(MAX(CASE WHEN section_code REGEXP '^[0-9]+$' THEN CAST(section_code AS UNSIGNED) ELSE 0 END), 0)
FROM work_sections
ON DUPLICATE KEY UPDATE current_value = GREATEST(current_value, VALUES(current_value));
