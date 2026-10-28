ALTER TABLE client_progress_statements
    ADD COLUMN cost_scope_snapshot LONGTEXT NULL,
    ADD CONSTRAINT chk_client_statement_cost_scope CHECK (cost_scope_snapshot IS NULL OR JSON_VALID(cost_scope_snapshot));
