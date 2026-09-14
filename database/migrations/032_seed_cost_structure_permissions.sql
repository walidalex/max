INSERT INTO permissions (code, name, module, is_system) VALUES
('cost_structure.view', 'عرض هيكل التكاليف', 'cost_structure', 1),
('cost_structure.manage', 'إدارة هيكل التكاليف', 'cost_structure', 1)
ON DUPLICATE KEY UPDATE code = VALUES(code);
