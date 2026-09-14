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
