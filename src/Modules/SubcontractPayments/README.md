# Subcontract Payments

This module records cash, transfer, cheque, and other payments against subcontractor earned work. Progress is approved only by `SubcontractCertificates`; this module never creates or changes progress.

The permanent financial invariant is: cumulative posted payments must not exceed cumulative approved earned value. Draft and cancelled payments do not consume entitlement. Posting locks the subcontract, then the draft payment, re-reads approved entitlement and posted payments, validates chronology and available value, freezes financial snapshots, and records the authenticated posting user in one transaction.

Statuses are `draft -> posted` or `draft -> cancelled`. Posted and cancelled records are terminal; posted payments are immutable and require a future dedicated reversal transaction for corrections.

Permissions are `subcontract_payments.view`, `create`, `edit`, `post`, and `cancel`. Future retention, deductions, penalties, tax, advances, recovery, reversal, and accounting entries are deliberately outside this module.
