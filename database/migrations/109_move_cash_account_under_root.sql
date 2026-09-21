UPDATE accounts a JOIN accounting_setup s ON s.account_id=a.id AND s.mapping_key='cash_on_hand' JOIN accounts r ON r.account_code='111100' SET a.parent_id=r.id
