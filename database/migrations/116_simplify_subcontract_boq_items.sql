ALTER TABLE subcontract_boq_items
    DROP FOREIGN KEY fk_subcontract_boq_items_cost,
    DROP COLUMN cost_code_id,
    DROP COLUMN item_code;
