INSERT INTO units (code, name_ar, symbol_ar, sort_order) VALUES
('lump_sum', 'مقطوعية', 'مقطوعية', 1), ('pcs', 'عدد', 'عدد', 2),
('day', 'يومية', 'يوم', 3), ('m2', 'متر مسطح', 'م²', 4),
('lm', 'متر طولي', 'م.ط', 5), ('thousand_bricks', 'ألف طوبة', 'ألف طوبة', 6)
ON DUPLICATE KEY UPDATE code = VALUES(code);
