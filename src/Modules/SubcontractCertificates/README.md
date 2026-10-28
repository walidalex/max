# Subcontract Certificates

Certificates record execution for the current period against an approved subcontract BOQ.
Drafts may be edited or cancelled; approval freezes quantities, amounts and snapshots.

- Quantity items accept the current-period quantity or the cumulative percentage of the full contracted quantity. Cumulative percentage is converted to total quantity, then previously approved quantity is subtracted under the subcontract lock. Values below approved execution are rejected.
- Percentage input is converted server-side with SQL DECIMAL arithmetic, rounded to four quantity decimal places. Stored execution remains a quantity; forms default to cumulative percentage calculated from approved previous execution plus the draft quantity.
- Lump-sum items accept percentage execution only.
- Approval locks the subcontract, recalculates approved previous execution and rejects cumulative execution beyond the contract basis.
- Monetary totals are calculated in SQL. Browser calculations are previews.
- Approved earned values are reused by subcontract payments.

Printing is available at `/subcontract-certificates/{id}/print` with `subcontract_certificates.view`. The A4 landscape report contains financial summary, posted payments, BOQ detail and signature spaces. Drafts are marked and use live previews. Approval atomically freezes contract/project/client/vendor identity, posted payment rows and summary balances in `print_snapshot`; later payments cannot change that report. Existing approved certificates without a snapshot explicitly show current payment data, not a reconstructed historical claim. Company branding uses the current company profile and an internally resolved logo data URI. No payment, cost or journal is created by printing.
