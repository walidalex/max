# Architecture

The application is a modular monolith. HTTP requests enter through `public/index.php` and follow:

```text
Route -> Controller -> Validator / DTO -> Service -> Repository -> MySQLi -> MySQL / MariaDB
```

`src/Core` owns infrastructure. `src/Shared` contains small cross-cutting helpers. Each business capability remains inside one folder under `src/Modules`.

Dependencies are resolved by the small application container through constructor type hints. This keeps the foundation testable without adding a framework or container package.

## Access control

Routes declare `auth` and `permission:<code>` middleware. `AuthenticationService` validates the active user and idle timeout on every protected request. `AuthorizationService` is the single authorization decision point and reloads role/permission relationships from the database for each check, so changes never remain stale in a logged-in session. The `super_admin` bypass exists only in that service.

State-changing POST controllers currently validate CSRF tokens explicitly. Login throttling and brute-force protection are required before public production deployment and remain a planned hardening item.

## Company profile

`CompanyProfileService` and `CompanyProfileRepository` always address record `id = 1`; there is no company collection or switching. Logos are validated by content, stored outside `public`, and streamed only through a protected route. The stored extension is selected from the detected MIME type without image conversion.

## Clients

The Clients module follows the standard controller, validator/DTO, service, repository flow. Its list uses server-side DataTables with whitelisted sort columns and prepared filter values. Client/contact compound writes and primary-contact changes use database transactions. `NumberGeneratorService` currently exposes only client code generation, backed by an atomic database sequence.

## Vendors

Suppliers and subcontractors share the Vendors module and master table. The list uses the shared server-side DataTables helper. Contact create/edit uses one Tabler Bootstrap modal and JSON responses; validation errors stay in the modal, while successful writes refresh only the shared contacts component. Contact primary changes remain transactional.

## Project actual costs

ProjectCosts owns the central recognized-cost ledger. Approval locks Project, Cost Entry, Cost Code/Work Section, then optional Vendor; it freezes classification snapshots and makes the entry immutable. Project and global ledgers reuse one server-side DataTables query. Source identities prevent future integrations from posting the same source line twice. Recognized cost is not payment.

## Projects

The Projects module owns project master data only. Codes use atomic yearly sequences. Client contacts and active project managers are validated server-side, while lifecycle transitions use separate authorization.

## Cost structure

The Cost Structure module manages work sections, cost codes, and units through the standard controller, validator/DTO, service, and repository flow. Lifecycle and uniqueness rules live in the service, while all SQL remains in repositories. The Arabic RTL management page groups cost codes by expandable work section and restricts selectors to active master records.

## Client contracts

Client Contracts owns the single main agreement header for each project. Internal codes use a yearly atomic sequence based on the contract date. Project, client, and contact consistency is repeated server-side. Commercial fields become immutable after draft activation, while future commercial changes belong to addendums. Projects with a contract cannot change client through normal project editing.

## Contract variations

Contract Variations is the shared commercial header for addendums and variations. Creation is limited to active or suspended contracts. Approval and cancellation are separate permission-protected actions with authenticated-user audit fields. Approved commercial data is immutable except for administrative notes, and the original contract value is never updated.

## Contract BOQ

Contract BOQ preserves the original scope as section, item, and unit snapshots while retaining optional Cost Structure links. GET is read-only and scope creation is explicit. Approval is transactional, immutable, and required before activation for BOQ-priced contracts or any started optional original scope.

## Subcontracts

Subcontracts and SubcontractBoq are isolated modules for project execution agreements. They reuse the validated BOQ snapshot and DECIMAL patterns without sharing client BOQ tables. Vendor eligibility is enforced server-side for new assignments while historical relationships remain readable.

## Subcontract progress certificates

`SubcontractCertificates` owns earned-work certification separately from contracts, BOQ definition, payments, and accounting. Approval locks the parent subcontract row, re-reads approved history, recalculates all DECIMAL quantities or percentages, validates contractual ceilings, and freezes audit and earned-value snapshots in one transaction. `getApprovedEarnedValue()` is the future payment integration boundary.

Architectural invariant: cumulative subcontractor payments must never exceed cumulative approved earned work. `SubcontractPaymentService` enforces this server-side while holding the relevant transaction locks; UI validation is not sufficient.

## Subcontract payments

`SubcontractPayments` consumes approved entitlement from `SubcontractCertificates` and never creates or modifies progress. Posting uses the subcontract row as the shared financial lock, re-reads entitlement and posted totals, freezes snapshots, and enforces `cumulative posted payments <= cumulative approved earned value` in one transaction.
Client Progress Statements use the Client Contract row as the shared financial lock. Cost Plus approval re-reads approved Project Actual Costs and approved Variations, calculates with SQL DECIMAL, freezes snapshots, commits allocations, and allocates the per-contract statement sequence in one transaction.

## Client receipts

`ClientReceipts` separates posted cash from approved receivables. Posting freezes contract identity without changing statements, costs, progress, or contract value. Append-only allocations settle approved statements and derive outstanding/unallocated balances. Allocation shares the Client Contract lock with statement approval and detects over-allocation or cross-contract corruption before accepting new settlement rows.
# Accounting foundation

Accounting Phase 1 is isolated under `src/Modules/Accounting`, with employees and employee custodies in their own modules. Posted journal entries are immutable and source identity prevents duplicate automatic posting. Operational Project Actual Costs remain separate from the GL; only employee-custody expense settlements create both ledgers in one transaction.
