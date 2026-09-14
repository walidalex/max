INSERT INTO permissions (code, name, module, is_system)
VALUES
    ('vendors.view', 'عرض الموردين ومقاولي الباطن', 'vendors', 1),
    ('vendors.create', 'إضافة الموردين ومقاولي الباطن', 'vendors', 1),
    ('vendors.edit', 'تعديل الموردين ومقاولي الباطن', 'vendors', 1),
    ('vendors.activate', 'تفعيل وتعطيل الموردين ومقاولي الباطن', 'vendors', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), module = VALUES(module), is_system = VALUES(is_system);
