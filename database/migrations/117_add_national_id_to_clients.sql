ALTER TABLE clients
    ADD COLUMN national_id VARCHAR(30) NULL AFTER company_name,
    ADD KEY idx_clients_national_id (national_id);
