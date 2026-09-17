INSERT INTO permissions(code,name,module,is_system) VALUES
('purchase_orders.view','عرض أوامر الشراء','purchase_orders',1),
('purchase_orders.create','إنشاء أوامر الشراء','purchase_orders',1),
('purchase_orders.edit','تعديل مسودات أوامر الشراء','purchase_orders',1),
('purchase_orders.approve','اعتماد أوامر الشراء','purchase_orders',1),
('purchase_orders.cancel','إلغاء مسودات أوامر الشراء','purchase_orders',1)
ON DUPLICATE KEY UPDATE name=VALUES(name),module=VALUES(module),is_system=VALUES(is_system);
