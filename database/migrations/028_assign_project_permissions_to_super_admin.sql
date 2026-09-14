INSERT IGNORE INTO role_permissions (role_id,permission_id)
SELECT r.id,p.id FROM roles r INNER JOIN permissions p ON p.code IN ('projects.view','projects.create','projects.edit','projects.change_status')
WHERE r.code='super_admin';
