INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id FROM roles CROSS JOIN permissions
WHERE roles.is_system = 1 AND permissions.code IN ('company_profile.view', 'company_profile.edit');
