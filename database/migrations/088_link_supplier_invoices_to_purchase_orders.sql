ALTER TABLE supplier_invoices
    ADD COLUMN purchase_order_id BIGINT UNSIGNED NULL AFTER project_id,
    ADD COLUMN purchase_order_code_snapshot VARCHAR(30) NULL AFTER project_name_snapshot,
    ADD KEY idx_supplier_invoices_purchase_order (purchase_order_id),
    ADD CONSTRAINT fk_supplier_invoices_purchase_order FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE RESTRICT,
    DROP CONSTRAINT chk_supplier_invoices_audit,
    ADD CONSTRAINT chk_supplier_invoices_audit CHECK (
        (status='draft' AND total_amount IS NULL AND vendor_code_snapshot IS NULL AND vendor_name_snapshot IS NULL AND project_code_snapshot IS NULL AND project_name_snapshot IS NULL AND purchase_order_code_snapshot IS NULL AND approved_at IS NULL AND approved_by IS NULL AND cancelled_at IS NULL AND cancelled_by IS NULL)
        OR (status='approved' AND total_amount>0 AND vendor_code_snapshot IS NOT NULL AND vendor_name_snapshot IS NOT NULL AND project_code_snapshot IS NOT NULL AND project_name_snapshot IS NOT NULL AND ((purchase_order_id IS NULL AND purchase_order_code_snapshot IS NULL) OR (purchase_order_id IS NOT NULL AND purchase_order_code_snapshot IS NOT NULL)) AND approved_at IS NOT NULL AND approved_by IS NOT NULL AND cancelled_at IS NULL AND cancelled_by IS NULL)
        OR (status='cancelled' AND total_amount IS NULL AND vendor_code_snapshot IS NULL AND vendor_name_snapshot IS NULL AND project_code_snapshot IS NULL AND project_name_snapshot IS NULL AND purchase_order_code_snapshot IS NULL AND approved_at IS NULL AND approved_by IS NULL AND cancelled_at IS NOT NULL AND cancelled_by IS NOT NULL)
    );
