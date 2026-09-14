INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r INNER JOIN permissions p ON p.code IN ('project_costs.view','project_costs.create','project_costs.edit','project_costs.approve','project_costs.cancel')
WHERE r.code='super_admin'
ON DUPLICATE KEY UPDATE role_id=VALUES(role_id);
