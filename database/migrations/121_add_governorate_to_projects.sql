ALTER TABLE projects
    ADD COLUMN governorate VARCHAR(40) NULL AFTER site_address,
    ADD KEY idx_projects_governorate (governorate);
