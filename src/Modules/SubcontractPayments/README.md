# Subcontract Payments

The sidebar directory at `/subcontract-payments` lists all payments using server-side DataTables with vendor, project, contract, status, method and date filters. The authenticated API at `/api/subcontract-payments` requires `subcontract_payments.view`. Contract-specific payment pages remain available. Creation starts by choosing a contract and continues to the existing secured form with current entitlement; `subcontract_payments.create` remains required.

This module records cash, transfer, cheque, and other payments against subcontractor earned work. Progress is approved only by `SubcontractCertificates`; this module never creates or changes progress.

The permanent financial invariant is: cumulative posted payments must not exceed cumulative approved earned value. Draft and cancelled payments do not consume entitlement. Posting locks the subcontract, then the draft payment, re-reads approved entitlement and posted payments, validates chronology and available value, freezes financial and payment-account snapshots, creates a posted journal entry (debit subcontract payable, credit the selected treasury/bank account), and records the authenticated posting user in one transaction.

Statuses are `draft -> posted` or `draft -> cancelled`. Posted and cancelled records are terminal; posted payments are immutable and require a future dedicated reversal transaction for corrections.

Creating or editing a draft locks the subcontract and rejects amounts above current available entitlement (approved earned value minus posted payments), using SQL DECIMAL comparison. Drafts do not reserve entitlement; posting always revalidates availability.

Payment accounts are active postable descendants of the configured `cash_accounts_root` and `bank_accounts_root` accounts. Adding another treasury or bank therefore only requires adding its postable account beneath the appropriate root in the chart of accounts. Permissions are `subcontract_payments.view`, `create`, `edit`, `post`, and `cancel`. Future retention, deductions, penalties, tax, advances, recovery, and reversal are deliberately outside this module.
