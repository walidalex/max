INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p ON p.code IN ('vendors.view', 'vendors.create', 'vendors.edit', 'vendors.activate')
WHERE r.is_system = 1;
