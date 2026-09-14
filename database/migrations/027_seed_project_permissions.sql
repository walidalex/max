INSERT INTO permissions (code,name,module,is_system) VALUES
('projects.view','عرض المشاريع','projects',1),('projects.create','إضافة المشاريع','projects',1),
('projects.edit','تعديل المشاريع','projects',1),('projects.change_status','تغيير حالة المشاريع','projects',1)
ON DUPLICATE KEY UPDATE name=VALUES(name),module=VALUES(module),is_system=VALUES(is_system);
