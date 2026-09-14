INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r INNER JOIN permissions p ON p.code IN ('cost_structure.view','cost_structure.manage')
WHERE r.code = 'super_admin' AND NOT EXISTS (SELECT 1 FROM role_permissions rp WHERE rp.role_id = r.id AND rp.permission_id = p.id)
ON DUPLICATE KEY UPDATE role_id = VALUES(role_id);
