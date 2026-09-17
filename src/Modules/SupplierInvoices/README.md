# Supplier Invoices

This module records supplier invoices. A draft contains a vendor, project, commercial invoice number, dates, and one or more Cost Code lines. Approval is terminal and atomically recognizes both the supplier payable and one approved Project Actual Cost per line.

Project costs use `source_type=supplier_invoice`, the invoice ID as `source_id`, and the line ID as `source_line_id`. The existing unique source identity prevents duplicate recognition. Approved headers and line classification snapshots are immutable. Cancelled drafts have no financial effect.

The approved invoice total is the current supplier payable subledger amount. Supplier Payments, VAT, GL, purchase orders, inventory, credit notes, and reversals are intentionally outside this module.
