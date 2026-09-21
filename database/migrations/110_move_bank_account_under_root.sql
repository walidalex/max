UPDATE accounts a JOIN accounting_setup s ON s.account_id=a.id AND s.mapping_key='bank_account' JOIN accounts r ON r.account_code='111200' SET a.parent_id=r.id
