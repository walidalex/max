# Database

- Engine: MySQL 8+ or MariaDB 10.11+
- Encoding: `utf8mb4`
- Access: object-oriented MySQLi
- Variable data: prepared statements only
- Schema changes: ordered SQL files in `database/migrations`

Run migrations with `php database/migrate.php` after creating the database and configuring `.env`.

## RBAC tables

- `users`: required unique username, nullable unique email, password hash, active state, login timestamp.
- `roles`: named roles with unique codes and protected system marker.
- `permissions`: immutable core permission codes grouped by module.
- `user_roles`: many-to-many user role assignments.
- `role_permissions`: many-to-many role permission assignments.

Users are never physically deleted. Access is revoked with `users.is_active`.

## Company profile

`company_profile` is a database-enforced singleton. Its check constraint permits only `id = 1`, and the migration seeds that record with a placeholder name. Optional attributes remain `NULL` until configured. `logo_path` contains a relative storage path only.

## Clients

- `clients` stores the immutable business code separately from its internal numeric ID. Records are activated or deactivated and never physically deleted.
- `client_type` is `VARCHAR(20)` and restricted to `individual` or `company` by both application validation and a database check constraint.
- `client_contacts` allows multiple contacts. A generated nullable slot with a unique key enforces at most one primary contact per client.
- `number_sequences` provides an atomic counter. Its current use is limited to generating client codes such as `CL-0001`; other document types may adopt different numbering policies later.

## Vendors

- `vendors` is the shared master record for suppliers, subcontractors, and entities serving as both. `vendor_type` is a validated `VARCHAR(20)` with a database check constraint.
- `vendor_contacts` supports multiple contacts and enforces at most one primary contact with a generated nullable slot and unique key.
- Vendor codes use the independent `vendors` sequence and are generated as `VN-0001`. Vendors are deactivated rather than physically deleted.

## Project actual costs

`project_actual_costs` is the central recognized-cost ledger. Only rows with `status = 'approved'` contribute to project cost totals. Amounts use `DECIMAL(18,2)` and remain decimal strings in PHP. Approval freezes Work Section and Cost Code codes/names. Manual entries have `source_type = manual` and NULL source IDs; future sources use a unique deterministic `(source_type, source_id, source_line_id)` identity. Payments never create project costs.

## Projects

- `projects` references one client and optionally a contact belonging to that client and an active user as project manager.
- Types and statuses use validated strings with database check constraints.
- Codes use yearly atomic sequences in the format `PRJ-YYYY-NNNN`; financial totals remain outside the project master.

## Cost structure

- `work_sections` stores the approved work-section codes; gaps in the source numbering are preserved.
- `cost_codes` belongs to one section and may reference a default unit. Both foreign keys use `ON DELETE RESTRICT` so historical/default relationships cannot be removed accidentally.
- `units`, sections, and cost codes use lifecycle flags instead of deletion. Deactivation never cascades to child flags.
- Inactive sections and units cannot be selected for new assignments. Existing relationships remain unchanged and may still be displayed historically.
- Seed migrations are repeat-safe with no-op `ON DUPLICATE KEY UPDATE` clauses and never overwrite edited master data.

## Client contracts

- `client_contracts` stores one main commercial agreement per project, enforced by a unique `project_id`.
- `contract_code` is an immutable internal yearly code generated from the year of `contract_date`; `contract_number` is the nullable commercial reference and must exist before activation.
- Project/client relationships use `ON DELETE RESTRICT`; an optional deleted contact is set to `NULL`.
- Pricing values are constrained by `pricing_method`. Contract execution, payments, BOQ lines, and profitability remain outside this header table.

## Contract variations

- `contract_variations` stores addendums, additional work, deductions, and commercial adjustments without changing the original contract value.
- Monetary values are non-negative and `amount_effect` expresses increase or decrease. Non-financial addendums use `none` with `NULL` amount and markup.
- Reference numbers are unique per parent contract while multiple `NULL` values are allowed.
- Approval and cancellation record their timestamp and authenticated user. Approved increases and decreases are derived for display and are not persisted on the contract.

## Contract BOQ

- One optional original BOQ belongs to each client contract. Sections and items store contractual snapshots beside optional master-data references.
- Items may be unpriced scope lines for lump-sum and cost-plus contracts; BOQ-priced contracts require unit, quantity, and rate.
- `line_total` is a stored generated DECIMAL value. Section and grand totals are derived with SQL.
- Approval freezes the original BOQ; for BOQ pricing it atomically synchronizes the exact total to the base contract value.

## Subcontracts

- Projects may have multiple subcontractor agreements. Commercial numbers are unique only within project, vendor, and number while `NULL` may repeat.
- New agreements require an active vendor of type `subcontractor` or `both`; later vendor deactivation does not invalidate historical contracts.
- Subcontract BOQ uses separate snapshot tables and generated DECIMAL totals. BOQ approval atomically synchronizes value only for BOQ-priced subcontracts.

## Subcontract progress certificates

- `subcontract_progress_certificates` stores immutable yearly system codes, optional subcontract-scoped business numbers, lifecycle/audit data, and frozen approval earned-value totals.
- BOQ certificates snapshot every approved original BOQ line in `subcontract_progress_items`. Only current quantity is entered; contractual quantity, rate, prior quantity, cumulative quantity, and amounts are system controlled.
- Lump-sum certificates store explicit previous/current/cumulative progress percentages on the header and do not create artificial item rows.
- Only approved certificates contribute to certified quantities and earned value. Draft and cancelled certificates never contribute.
- Approved certificates cannot be edited, cancelled, or deleted. A future correction requires a dedicated reversal mechanism.
- The original approved BOQ is never changed by progress certification. Future scope or quantity changes belong to a separate Subcontract Variations module and must later participate in effective certifiable-quantity calculations.

## Subcontract payments

- Payments snapshot approved progress, earned value, previous posted payments, and the available ceiling at posting.
- Only posted payments consume entitlement. Draft and cancelled payments do not count, and posted payments are immutable.
- Posting serializes on the subcontract row and enforces both the earned-value ceiling and non-retroactive payment dates. Draft dates cannot move outside the year encoded in their immutable payment code.
Client Progress Statements are stored in client_progress_statements, client_progress_statement_costs, and client_progress_statement_variations. A nullable generated committed-cost identity with a UNIQUE index prevents one approved Project Actual Cost from being billed twice while allowing draft selections.

## Client receipts

`client_receipts` stores immutable posted cash events with frozen client/project/contract identity. `client_receipt_allocations` stores append-only settlement rows against approved Client Progress Statements. Receipt and statement balances are derived with SQL `DECIMAL`; no paid or balance columns are added to statements. Foreign keys use `ON DELETE RESTRICT`, and the Client Contract row serializes allocation activity.

## Purchase orders

- `purchase_orders` records a vendor commitment for a project; approval has no project-cost, payable, payment, inventory, tax, or GL effect.
- `purchase_order_lines` stores DECIMAL quantities and unit prices. MariaDB generates each `line_total`, and the approved header freezes the SQL-derived total.
- The SQL sum is checked against the full `DECIMAL(18,2)` range before saving and again before approval.
- Drafts are editable or cancellable. Approved and cancelled orders are immutable, and approval freezes vendor, project, work-section, and cost-code snapshots.
- Codes use an independent yearly atomic sequence in the format `PO-YYYY-NNNN`.
- `supplier_invoices.purchase_order_id` optionally references an approved order with the same vendor and project. Invoice approval locks and revalidates the order and freezes `purchase_order_code_snapshot`; the order itself remains commitment-only.
