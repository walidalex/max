INSERT INTO company_profile (id, name) VALUES (1, 'اسم الشركة') ON DUPLICATE KEY UPDATE id = id;
