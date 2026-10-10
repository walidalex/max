INSERT INTO subcontract_boqs (subcontract_id, status)
SELECT s.id, 'draft'
FROM subcontracts s
LEFT JOIN subcontract_boqs b ON b.subcontract_id = s.id
WHERE b.id IS NULL;
