INSERT INTO permissions (code, name, module, is_system)
VALUES
    ('clients.view', 'عرض العملاء', 'clients', 1),
    ('clients.create', 'إضافة العملاء', 'clients', 1),
    ('clients.edit', 'تعديل العملاء', 'clients', 1),
    ('clients.activate', 'تفعيل وتعطيل العملاء', 'clients', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), module = VALUES(module), is_system = VALUES(is_system);
