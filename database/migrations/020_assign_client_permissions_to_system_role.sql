INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p ON p.code IN ('clients.view', 'clients.create', 'clients.edit', 'clients.activate')
WHERE r.is_system = 1;
