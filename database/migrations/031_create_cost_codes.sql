CREATE TABLE IF NOT EXISTS cost_codes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    work_section_id BIGINT UNSIGNED NOT NULL,
    cost_code VARCHAR(30) NOT NULL,
    name VARCHAR(255) NOT NULL,
    default_unit_id BIGINT UNSIGNED NULL,
    description TEXT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cost_codes_code (cost_code),
    KEY idx_cost_codes_section_active_sort (work_section_id, is_active, sort_order),
    KEY idx_cost_codes_default_unit (default_unit_id),
    CONSTRAINT fk_cost_codes_work_section FOREIGN KEY (work_section_id) REFERENCES work_sections(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_cost_codes_default_unit FOREIGN KEY (default_unit_id) REFERENCES units(id) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
