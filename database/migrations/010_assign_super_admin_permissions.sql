INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id FROM roles CROSS JOIN permissions WHERE roles.code = 'super_admin' AND permissions.is_system = 1;
