ALTER TABLE vendors
    ADD COLUMN bank_name VARCHAR(60) NULL AFTER email,
    ADD KEY idx_vendors_bank_name (bank_name);
