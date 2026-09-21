# Subcontract Payments

This module records cash, transfer, cheque, and other payments against subcontractor earned work. Progress is approved only by `SubcontractCertificates`; this module never creates or changes progress.

The permanent financial invariant is: cumulative posted payments must not exceed cumulative approved earned value. Draft and cancelled payments do not consume entitlement. Posting locks the subcontract, then the draft payment, re-reads approved entitlement and posted payments, validates chronology and available value, freezes financial and payment-account snapshots, creates a posted journal entry (debit subcontract payable, credit the selected treasury/bank account), and records the authenticated posting user in one transaction.

Statuses are `draft -> posted` or `draft -> cancelled`. Posted and cancelled records are terminal; posted payments are immutable and require a future dedicated reversal transaction for corrections.

Payment accounts are active postable descendants of the configured `cash_accounts_root` and `bank_accounts_root` accounts. Adding another treasury or bank therefore only requires adding its postable account beneath the appropriate root in the chart of accounts. Permissions are `subcontract_payments.view`, `create`, `edit`, `post`, and `cancel`. Future retention, deductions, penalties, tax, advances, recovery, and reversal are deliberately outside this module.
