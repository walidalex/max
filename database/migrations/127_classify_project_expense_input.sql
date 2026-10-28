ALTER TABLE project_actual_costs
    ADD COLUMN expense_type VARCHAR(20) NULL,
    ADD CONSTRAINT chk_project_expense_type CHECK (expense_type IS NULL OR expense_type IN ('general','work'));
