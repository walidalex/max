CREATE TABLE IF NOT EXISTS company_profile (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    legal_name VARCHAR(190) NULL,
    tax_number VARCHAR(80) NULL,
    commercial_registration VARCHAR(80) NULL,
    phone VARCHAR(30) NULL,
    mobile VARCHAR(30) NULL,
    email VARCHAR(190) NULL,
    website VARCHAR(255) NULL,
    address VARCHAR(500) NULL,
    logo_path VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_company_profile_singleton CHECK (id = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
