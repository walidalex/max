UPDATE accounts target
LEFT JOIN accounts revenue_root ON revenue_root.account_code = '400000'
SET target.parent_id = CASE WHEN target.account_code = '710000' THEN revenue_root.id ELSE target.parent_id END,
    target.name_ar = CASE WHEN target.account_code = '700000' THEN 'مصروفات أخرى' ELSE target.name_ar END,
    target.name_en = CASE WHEN target.account_code = '700000' THEN 'Other Expenses' ELSE target.name_en END
WHERE target.account_code IN ('700000', '710000');
