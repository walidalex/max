# Client Progress Statements

## Purpose

This module records client-facing progress statements for `cost_plus` and `boq` Client Contracts. Lump Sum billing is deliberately not implemented.

## BOQ billing

BOQ statements require an approved Client Contract BOQ. Every approved BOQ item is seeded into the draft with a zero current quantity. At approval the service locks the Client Contract, re-reads the approved BOQ, recalculates prior quantities and prior approved item amounts, prevents cumulative quantity above the contractual quantity, and freezes section, item, unit, quantity, rate, and amount snapshots.

Current item amount is `ROUND(current_quantity × unit_rate, 2)`. Previous amount is the sum of frozen `current_amount` values from earlier approved BOQ statements, not a recalculation using the current source rate. Current Statement is Current BOQ plus signed Variation allocations. BOQ never receives Cost Plus markup.

## Phase 1 billing assumption

All approved Project Actual Costs belonging to a Cost Plus project are eligible for client billing unless already committed to another approved Client Progress Statement and provided their `cost_date` is not later than the statement date. The current Project Costs model has no internal/non-billable classification; Phase 1 does not invent one.

`project_actual_costs` is the only recognized actual-cost source. Draft and cancelled costs never enter a statement. Each selected cost is billed in full; partial actual-cost billing is outside Phase 1.

## Cost Plus calculation

At approval the server re-reads every source and calculates with SQL `DECIMAL`:

- Current Cost = selected approved Project Actual Costs.
- Current Markup = `ROUND(Current Cost × contract markup_percentage / 100, 2)`.
- Current Variation = signed approved Variation allocations.
- Current Statement = Current Cost + Current Markup + Current Variation.

Contract markup applies only to actual costs, never automatically to Variations. Approval requires a positive Current Statement.

## Variations

Approved `increase` and `decrease` Variations support partial billing. The entered current allocation is always a positive magnitude; its sign comes from `amount_effect`. Approval locks the Client Contract, recalculates previous committed allocations, and rejects allocation beyond the approved amount. A Variation with a committed statement allocation cannot be normally cancelled; future correction requires a reversal or credit-adjustment workflow.

## No double billing and locking

Cost selections in drafts are non-committed. Approval changes them to committed rows. A generated nullable `committed_project_actual_cost_id` plus a UNIQUE index ensures one Project Actual Cost can belong to only one committed statement. Service validation provides the Arabic business error before the database constraint acts.

Approval uses one transaction and deterministic locks:

`Client Contract → Statement → Cost details → Project Actual Costs by ID → Variation details → Contract Variations by ID`.

The shared Client Contract lock serializes statement sequence allocation, cumulative history, Variation consumption, and Variation cancellation.

## Sequence, history, and snapshots

`statement_code` is immutable and generated per year as `CPS-YYYY-NNNN`. `statement_sequence` is allocated per contract only at approval and is the authoritative financial order. A statement cannot be approved earlier than the latest approved statement date; the same date is allowed.

The latest approved cumulative values become the next statement's previous values. Draft and cancelled statements have no financial effect. Approval freezes header identity, all financial values, cost details, and Variation details. Approved statements are terminal and financially immutable. Print views use frozen snapshots.

## Future integration

Client Receipts and Receipt Allocations settle approved `current_statement_amount` values without depending on pricing method. This module contains no paid, received, balance, receipt status, VAT, retention, penalty, or accounting-journal fields. Future phases may add Lump Sum billing and dedicated reversal/credit mechanisms without changing approved history.
