UPDATE client_contracts
SET contract_number = contract_code
WHERE contract_number IS NULL OR TRIM(contract_number) = '';
