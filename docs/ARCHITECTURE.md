# Architecture

The application is a modular monolith. HTTP requests enter through `public/index.php` and follow:

```text
Route -> Controller -> Validator / DTO -> Service -> Repository -> MySQLi -> MySQL / MariaDB
```

`src/Core` owns infrastructure. `src/Shared` contains small cross-cutting helpers. Each future business capability must remain inside one folder under `src/Modules`.

Dependencies are resolved by the small application container through constructor type hints. This keeps the foundation testable without adding a framework or container package.

## Access control

Routes declare `auth` and `permission:<code>` middleware. `AuthenticationService` validates the active user and idle timeout on every protected request. `AuthorizationService` is the single authorization decision point and reloads role/permission relationships from the database for each check, so changes never remain stale in a logged-in session. The `super_admin` bypass exists only in that service.

## Company profile

`CompanyProfileService` and `CompanyProfileRepository` always address record `id = 1`; there is no company collection or switching. Logos are validated by content, stored outside `public`, and streamed only through a protected route. The stored extension is selected from the detected MIME type without image conversion.

## Clients

The Clients module follows the standard controller, validator/DTO, service, repository flow. Its list uses server-side DataTables with whitelisted sort columns and prepared filter values. Client/contact compound writes and primary-contact changes use database transactions. `NumberGeneratorService` currently exposes only client code generation, backed by an atomic database sequence.

## Vendors

Suppliers and subcontractors share the Vendors module and master table. The list uses the shared server-side DataTables helper. Contact create/edit uses one Tabler Bootstrap modal and JSON responses; validation errors stay in the modal, while successful writes refresh only the shared contacts component. Contact primary changes remain transactional.

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
