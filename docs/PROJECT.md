# Project

This repository contains an Arabic RTL contracting and interior design ERP implemented as a PHP 8.4 modular monolith.

## Current scope

- Foundation/Core, authentication, RBAC, company profile, clients, vendors, projects, and Cost Structure.
- Client Contracts, Contract Variations, Client BOQ, and Cost Plus Client Progress Statements.
- Subcontracts, Subcontract BOQ, Subcontract Progress Certificates, and Subcontract Payments.
- Project Actual Costs as the central recognized-cost ledger. Only approved entries count; payments represent settlement and must not duplicate recognized cost.

## Planned scope

- Client Receipts and Receipt Allocation.
- BOQ Client Progress Statements.
- Supplier Invoices and Supplier Payments.
- Procurement and Purchase Orders.
- Cost Control, profitability, reports, and dashboard.
- Financial reversals and credit adjustments.
- Login throttling/brute-force protection before public production deployment.
