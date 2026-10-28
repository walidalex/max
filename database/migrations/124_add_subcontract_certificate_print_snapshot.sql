ALTER TABLE subcontract_progress_certificates
    ADD COLUMN print_snapshot LONGTEXT NULL,
    ADD CONSTRAINT chk_subcontract_certificate_print_snapshot CHECK (print_snapshot IS NULL OR JSON_VALID(print_snapshot));
