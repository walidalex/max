CREATE TABLE IF NOT EXISTS vendor_contacts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    vendor_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    job_title VARCHAR(120) NULL,
    phone VARCHAR(30) NULL,
    mobile VARCHAR(30) NULL,
    email VARCHAR(190) NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    primary_slot TINYINT GENERATED ALWAYS AS (CASE WHEN is_primary = 1 THEN 1 ELSE NULL END) STORED,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_vendor_contacts_vendor_id (vendor_id),
    UNIQUE KEY uq_vendor_contacts_primary (vendor_id, primary_slot),
    CONSTRAINT fk_vendor_contacts_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
