INSERT INTO permissions (code, name, module, is_system) VALUES
('project_costs.view','عرض التكاليف الفعلية للمشروعات','project_costs',1),
('project_costs.create','إضافة تكاليف فعلية للمشروعات','project_costs',1),
('project_costs.edit','تعديل مسودات التكاليف الفعلية','project_costs',1),
('project_costs.approve','اعتماد التكاليف الفعلية','project_costs',1),
('project_costs.cancel','إلغاء مسودات التكاليف الفعلية','project_costs',1)
ON DUPLICATE KEY UPDATE name=VALUES(name), module=VALUES(module), is_system=VALUES(is_system);
