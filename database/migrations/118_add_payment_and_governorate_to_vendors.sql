ALTER TABLE vendors
    ADD COLUMN bank_account VARCHAR(190) NULL AFTER email,
    ADD COLUMN instapay_number VARCHAR(100) NULL AFTER bank_account,
    ADD COLUMN governorate VARCHAR(40) NULL AFTER instapay_number,
    ADD KEY idx_vendors_governorate (governorate);
