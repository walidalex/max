INSERT INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.code IN('purchase_orders.view','purchase_orders.create','purchase_orders.edit','purchase_orders.approve','purchase_orders.cancel')
WHERE r.code='super_admin' ON DUPLICATE KEY UPDATE role_id=VALUES(role_id);
