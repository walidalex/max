ALTER TABLE project_actual_costs
    ADD COLUMN subcontract_boq_item_id BIGINT UNSIGNED NULL,
    ADD COLUMN scope_snapshot LONGTEXT NULL,
    ADD KEY idx_actual_cost_scope (subcontract_boq_item_id),
    ADD CONSTRAINT fk_actual_cost_scope FOREIGN KEY (subcontract_boq_item_id) REFERENCES subcontract_boq_items(id) ON DELETE RESTRICT,
    ADD CONSTRAINT chk_actual_cost_scope_snapshot CHECK (scope_snapshot IS NULL OR JSON_VALID(scope_snapshot));
