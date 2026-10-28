# Client Progress Statements

## Cost Plus project scope

The contract directory, draft, approved view and print include all items from approved subcontract scopes on the same project, grouped by work section. Quantity/lump-sum execution is read only from approved subcontract certificates dated on or before the client statement. Previous execution is the preceding client statement's frozen cumulative execution; current is the difference. For statements predating this feature, the preceding approved statement date is used as the initial execution cutoff.

New manual costs select `expense_type`: `work` selects a work section and one of its existing cost-code children; `general` selects an accounting classification and is grouped as a general expense. No subcontract BOQ selection or manual description is used: the service derives the description from the selected cost code and validates parent ownership and active masters. Cost-code expense rows appear below subcontract scope rows in the same work-section group; their execution fields are empty. Subcontract progress remains restricted to scope rows.

Existing nullable classifications and any earlier scope links are preserved for compatibility and never rewritten automatically. Older/automatic costs without classification remain in the general/unallocated row. Approved client statement snapshots always take precedence over subsequent classification or master changes.

Expense classifications appear only after an approved `work` actual cost uses the
code on this project, dated on or before the statement. An approved subcontract
in the same work section does not populate all child cost codes. Draft expenses
and future expenses do not introduce rows. Historical rows with prior billed
amounts remain; unused zero-value classifications from older snapshots are not
carried into new statements. Approved snapshots remain unchanged.

Subcontract rows bill the approved executed value at the agreed subcontract prices.
The source is the sum of frozen `current_amount` values in approved subcontract
certificate items dated on or before the client statement. Legacy lump-sum
subcontracts use their approved certificate `current_earned_value`. Payments do
not determine this value. Each row deducts the cumulative subcontract value
already billed in the previous client statement snapshot. Draft/cancelled/future
certificates are excluded. Manual and supplier-invoice costs outside that scope
are selected separately. Both sources receive the client contract markup.
Group rounding can differ by a cent from globally rounded markup.

At client statement approval, a JSON `cost_scope_snapshot` freezes the complete scope, descriptions, execution and cost breakdown within the existing transaction. Subcontract rows are locked while approved progress is read, serializing against certificate approval. Later source edits, new scopes/certificates or new expenses cannot alter the approved report. Older approved client statements without this snapshot explicitly retain their original financial details without fabricating historic scope.

## Purpose

This module records client-facing progress statements for `cost_plus` and `boq` Client Contracts. Lump Sum billing is deliberately not implemented.

The global directory includes a create button for `client_progress_statements.create`.
`/client-progress-statements/create` selects the project and an eligible contract,
then redirects to the existing contract-scoped form without creating a draft.
Eligible contracts are active, suspended, or completed Cost Plus contracts with
markup, or BOQ contracts with an approved BOQ. Selection and direct form access
repeat eligibility checks on the server; saving retains the existing transactional
checks. Contract-scoped lists show their create button only when eligible.

## BOQ billing

BOQ statements require an approved Client Contract BOQ. Every approved BOQ item is seeded into the draft with a zero current quantity. At approval the service locks the Client Contract, re-reads the approved BOQ, recalculates prior quantities and prior approved item amounts, prevents cumulative quantity above the contractual quantity, and freezes section, item, unit, quantity, rate, and amount snapshots.

Current item amount is `ROUND(current_quantity × unit_rate, 2)`. Previous amount is the sum of frozen `current_amount` values from earlier approved BOQ statements, not a recalculation using the current source rate. Current Statement is Current BOQ plus signed Variation allocations. BOQ never receives Cost Plus markup.

## Phase 1 billing assumption

Approved Project Actual Costs outside subcontract scope are eligible for client billing unless already committed to another approved Client Progress Statement and provided their `cost_date` is not later than the statement date. The current Project Costs model has no internal/non-billable classification; Phase 1 does not invent one.

Approved subcontract execution and selected `project_actual_costs` are the two
cost sources. Actual-cost rows linked to a subcontract scope item, certificate,
or payment are excluded from separate billing to avoid recognizing the same
subcontract execution twice. Unlinked manual expenses are separate charges and
must represent additional costs. Draft/cancelled costs are excluded; selected
actual costs are billed in full. Date changes refresh execution and eligible
expenses in the form through a permission-protected read-only preview endpoint.

## Cost Plus calculation

At approval the server re-reads every source and calculates with SQL `DECIMAL`:

- Current Cost = unbilled approved subcontract execution + selected approved Project Actual Costs outside that scope.
- Current Markup = `ROUND(Current Cost × contract markup_percentage / 100, 2)`.
- Current Variation = signed approved Variation allocations.
- Current Statement = Current Cost + Current Markup + Current Variation.

Contract markup applies to both cost sources, never automatically to Variations.
Approval requires a positive Current Statement. Recalculation and scope snapshot
freezing share the contract/subcontract transaction locks. Stale drafts cannot
bill previously approved execution again. Snapshots store previous/current/
cumulative subcontract costs separately from manual expense rows. Older frozen
reports remain unchanged; committed scope-linked actual costs provide the legacy
baseline when no subcontract billing keys exist in the prior snapshot.

## Variations

Approved `increase` and `decrease` Variations support partial billing. The entered current allocation is always a positive magnitude; its sign comes from `amount_effect`. Approval locks the Client Contract, recalculates previous committed allocations, and rejects allocation beyond the approved amount. A Variation with a committed statement allocation cannot be normally cancelled; future correction requires a reversal or credit-adjustment workflow.

## No double billing and locking

Cost selections in drafts are non-committed. Approval changes them to committed rows. A generated nullable `committed_project_actual_cost_id` plus a UNIQUE index ensures one Project Actual Cost can belong to only one committed statement. Service validation provides the Arabic business error before the database constraint acts.

Approval uses one transaction and deterministic locks:

`Client Contract → Statement → Cost details → Project Actual Costs by ID → Variation details → Contract Variations by ID → Project Subcontracts by ID`.

The shared Client Contract lock serializes statement sequence allocation, cumulative history, Variation consumption, and Variation cancellation.

## Sequence, history, and snapshots

`statement_code` is immutable and generated per year as `CPS-YYYY-NNNN`. `statement_sequence` is allocated per contract only at approval and is the authoritative financial order. A statement cannot be approved earlier than the latest approved statement date; the same date is allowed.

The latest approved cumulative values become the next statement's previous values. Draft and cancelled statements have no financial effect. Approval freezes header identity, all financial values, cost details, and Variation details. Approved statements are terminal and financially immutable. Print views use frozen snapshots.

## Future integration

Client Receipts and Receipt Allocations settle approved `current_statement_amount` values without depending on pricing method. This module contains no paid, received, balance, receipt status, VAT, retention, penalty, or accounting-journal fields. Future phases may add Lump Sum billing and dedicated reversal/credit mechanisms without changing approved history.
