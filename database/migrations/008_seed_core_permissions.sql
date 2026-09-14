INSERT INTO permissions (name, code, module, description, is_system) VALUES
('عرض المستخدمين', 'users.view', 'users', 'عرض قائمة وبيانات المستخدمين', 1),
('إنشاء المستخدمين', 'users.create', 'users', 'إنشاء مستخدم جديد', 1),
('تعديل المستخدمين', 'users.edit', 'users', 'تعديل بيانات المستخدم', 1),
('تفعيل المستخدمين', 'users.activate', 'users', 'تفعيل أو تعطيل المستخدم', 1),
('تعيين أدوار المستخدمين', 'users.assign_roles', 'users', 'تعيين الأدوار للمستخدم', 1),
('عرض الأدوار', 'roles.view', 'roles', 'عرض قائمة وبيانات الأدوار', 1),
('إنشاء الأدوار', 'roles.create', 'roles', 'إنشاء دور جديد', 1),
('تعديل الأدوار', 'roles.edit', 'roles', 'تعديل بيانات الدور', 1),
('تعيين صلاحيات الأدوار', 'roles.assign_permissions', 'roles', 'تعيين الصلاحيات للدور', 1),
('عرض الصلاحيات', 'permissions.view', 'permissions', 'عرض قائمة الصلاحيات', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), module = VALUES(module), description = VALUES(description), is_system = VALUES(is_system);
