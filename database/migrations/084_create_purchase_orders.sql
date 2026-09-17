CREATE TABLE purchase_orders (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    po_code VARCHAR(30) NOT NULL,
    vendor_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NOT NULL,
    po_date DATE NOT NULL,
    expected_date DATE NULL,
    reference VARCHAR(190) NULL,
    notes TEXT NULL,
    total_amount DECIMAL(18,2) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    vendor_code_snapshot VARCHAR(30) NULL,
    vendor_name_snapshot VARCHAR(190) NULL,
    project_code_snapshot VARCHAR(30) NULL,
    project_name_snapshot VARCHAR(190) NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    approved_at DATETIME NULL,
    approved_by BIGINT UNSIGNED NULL,
    cancelled_at DATETIME NULL,
    cancelled_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_purchase_orders_code (po_code),
    KEY idx_purchase_orders_project_status_date (project_id,status,po_date),
    KEY idx_purchase_orders_vendor_status_date (vendor_id,status,po_date),
    KEY idx_purchase_orders_expected_date (expected_date),
    CONSTRAINT fk_purchase_orders_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE RESTRICT,
    CONSTRAINT fk_purchase_orders_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE RESTRICT,
    CONSTRAINT fk_purchase_orders_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_purchase_orders_approved_by FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_purchase_orders_cancelled_by FOREIGN KEY (cancelled_by) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT chk_purchase_orders_status CHECK (status IN ('draft','approved','cancelled')),
    CONSTRAINT chk_purchase_orders_dates CHECK (expected_date IS NULL OR expected_date >= po_date),
    CONSTRAINT chk_purchase_orders_audit CHECK (
        (status='draft' AND total_amount IS NULL AND vendor_code_snapshot IS NULL AND vendor_name_snapshot IS NULL AND project_code_snapshot IS NULL AND project_name_snapshot IS NULL AND approved_at IS NULL AND approved_by IS NULL AND cancelled_at IS NULL AND cancelled_by IS NULL)
        OR (status='approved' AND total_amount>0 AND vendor_code_snapshot IS NOT NULL AND vendor_name_snapshot IS NOT NULL AND project_code_snapshot IS NOT NULL AND project_name_snapshot IS NOT NULL AND approved_at IS NOT NULL AND approved_by IS NOT NULL AND cancelled_at IS NULL AND cancelled_by IS NULL)
        OR (status='cancelled' AND total_amount IS NULL AND vendor_code_snapshot IS NULL AND vendor_name_snapshot IS NULL AND project_code_snapshot IS NULL AND project_name_snapshot IS NULL AND approved_at IS NULL AND approved_by IS NULL AND cancelled_at IS NOT NULL AND cancelled_by IS NOT NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
