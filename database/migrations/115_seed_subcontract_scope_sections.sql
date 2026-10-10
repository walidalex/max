INSERT INTO subcontract_boq_sections (subcontract_boq_id, work_section_id, section_code, title, sort_order)
SELECT b.id, ws.id, ws.section_code, ws.name, 1
FROM subcontract_boqs b
JOIN subcontracts s ON s.id = b.subcontract_id
JOIN work_sections ws ON ws.id = s.work_section_id
WHERE NOT EXISTS (
    SELECT 1
    FROM subcontract_boq_sections bs
    WHERE bs.subcontract_boq_id = b.id
);
