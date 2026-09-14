INSERT INTO roles (name, code, description, is_active, is_system)
VALUES ('مدير النظام', 'super_admin', 'دور النظام المحمي ذو الوصول الكامل', 1, 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), is_active = 1, is_system = 1;
