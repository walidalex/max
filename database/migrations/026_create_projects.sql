CREATE TABLE IF NOT EXISTS projects (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 project_code VARCHAR(30) NOT NULL UNIQUE, name VARCHAR(190) NOT NULL,
 client_id BIGINT UNSIGNED NOT NULL, primary_contact_id BIGINT UNSIGNED NULL, project_manager_id BIGINT UNSIGNED NULL,
 project_type VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'planning',
 start_date DATE NULL, expected_end_date DATE NULL, actual_end_date DATE NULL,
 site_address VARCHAR(500) NULL, city VARCHAR(120) NULL, area VARCHAR(120) NULL, description TEXT NULL, notes TEXT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 KEY idx_projects_client (client_id), KEY idx_projects_contact (primary_contact_id), KEY idx_projects_manager (project_manager_id),
 KEY idx_projects_type_status (project_type,status), KEY idx_projects_start (start_date),
 CONSTRAINT fk_projects_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
 CONSTRAINT fk_projects_contact FOREIGN KEY (primary_contact_id) REFERENCES client_contacts(id) ON DELETE SET NULL,
 CONSTRAINT fk_projects_manager FOREIGN KEY (project_manager_id) REFERENCES users(id) ON DELETE SET NULL,
 CONSTRAINT chk_projects_type CHECK (project_type IN ('interior_design','fit_out','contracting','renovation','maintenance','other')),
 CONSTRAINT chk_projects_status CHECK (status IN ('planning','active','on_hold','completed','cancelled')),
 CONSTRAINT chk_projects_dates CHECK (start_date IS NULL OR expected_end_date IS NULL OR expected_end_date >= start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
