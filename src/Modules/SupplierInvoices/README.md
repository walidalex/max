# Supplier Invoices

This module records supplier invoices. A draft contains a vendor, project, optional approved Purchase Order, commercial invoice number, dates, and one or more Cost Code lines. Approval is terminal and atomically recognizes both the supplier payable and one approved Project Actual Cost per line.

Project costs use `source_type=supplier_invoice`, the invoice ID as `source_id`, and the line ID as `source_line_id`. The existing unique source identity prevents duplicate recognition. A linked Purchase Order must be approved and match the invoice vendor/project; approval locks and revalidates it, then freezes its code on the invoice. Approved headers and line classification snapshots are immutable. Cancelled drafts have no financial effect.

The approved invoice total is the current supplier payable subledger amount. Purchase Order linking does not implement consumption, closing, receiving, or matching. VAT, GL, inventory, credit notes, and reversals remain outside this module.
