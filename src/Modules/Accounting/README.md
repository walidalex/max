# Accounting Foundation

Phase 1 provides a hierarchical chart of accounts, monthly fiscal periods, manual journals, posting mappings, a posted-only trial balance, and the accounting integration for employee custodies.

## Invariants

- Posted journals must have equal, positive debit and credit totals. Financial values remain SQL `DECIMAL`; PHP receives normalized decimal strings.
- Account hierarchy is defined by `parent_id`. Group accounts are non-postable, and only active postable accounts accept lines. Once an account has posted history, its code, type, normal balance, parent, postable flag, and control flag are immutable so historical balances remain visible.
- Protected control accounts are blocked in manual journals. Business documents may use them only through the automatic posting service.
- Client, vendor, project, subcontract, employee, and cost code are journal-line dimensions; they never create separate GL accounts.
- Automatic entries use unique `source_type` and `source_id` identity. Repeating a source is rejected.
- Posting requires an open accounting period. Posted journals are immutable; reversal entries are a future phase.

## Employee custody

Issuing custody debits Employee Custody and credits the cash/bank account selected through `accounting_setup`; it creates no Project Actual Cost. Expense settlement requires an explicit Accounting Setup mapping from its cost code to an active, postable expense account, then creates one approved Project Actual Cost and one balanced journal using the same settlement project dimension. A custody with a project can only be settled against that project; a custody without one may use any valid project. Cash return creates only a balanced journal. Settlement cannot predate issue, and posting still requires an open period. A locked custody row and SQL decimal comparison prevent over-settlement and duplicate effects.

Future phases may connect supplier invoices/payments, client statements/receipts, and subcontract certificates/payments to the same source-identity posting foundation. They are intentionally not auto-posted in this phase.
