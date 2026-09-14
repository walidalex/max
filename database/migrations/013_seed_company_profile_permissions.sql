INSERT INTO permissions (name, code, module, description, is_system) VALUES
('عرض ملف الشركة', 'company_profile.view', 'company_profile', 'عرض بيانات الشركة وشعارها', 1),
('تعديل ملف الشركة', 'company_profile.edit', 'company_profile', 'تعديل بيانات الشركة واستبدال شعارها', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), module = VALUES(module), description = VALUES(description), is_system = VALUES(is_system);
