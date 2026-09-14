INSERT INTO number_sequences (sequence_key, current_value)
VALUES ('vendors', 0)
ON DUPLICATE KEY UPDATE sequence_key = VALUES(sequence_key);
