# Accounting Foundation

Phase 1 provides a hierarchical chart of accounts, monthly fiscal periods, manual journals, posting mappings, a posted-only trial balance, and the accounting integration for employee custodies.

## Invariants

- Posted journals must have equal, positive debit and credit totals. Financial values remain SQL `DECIMAL`; PHP receives normalized decimal strings.
- Account hierarchy is defined by `parent_id`. Group accounts are non-postable, and only active postable accounts accept lines.
- Protected control accounts are blocked in manual journals. Business documents may use them only through the automatic posting service.
- Client, vendor, project, subcontract, employee, and cost code are journal-line dimensions; they never create separate GL accounts.
- Automatic entries use unique `source_type` and `source_id` identity. Repeating a source is rejected.
- Posting requires an open accounting period. Posted journals are immutable; reversal entries are a future phase.

## Employee custody

Issuing custody debits Employee Custody and credits the configured cash/bank account; it creates no Project Actual Cost. Expense settlement creates one approved Project Actual Cost and one balanced journal. Cash return creates only a balanced journal. A locked custody row and SQL decimal comparison prevent over-settlement and duplicate effects.

Future phases may connect supplier invoices/payments, client statements/receipts, and subcontract certificates/payments to the same source-identity posting foundation. They are intentionally not auto-posted in this phase.
