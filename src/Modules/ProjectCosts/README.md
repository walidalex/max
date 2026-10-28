# Project Actual Costs

Manual entry now chooses a general expense or work-item expense. A work expense selects a work section then an existing cost code from that section. General expenses retain a required accounting cost-code classification. The description is derived server-side from the code name; submitted descriptions cannot override it. No subcontract scope item is selected. Both kinds remain normal manual PAC entries and follow draft/approval validation; the `expense_type` field controls Cost Plus presentation only. Earlier NULL classifications and approved snapshots are preserved.

This module is the central recognized-cost ledger, classified by Project, Work Section, and Cost Code. Only approved Project Actual Cost entries contribute to recognized project cost totals.

Manual entries move from `draft` to either terminal `approved` or `cancelled`. Approval freezes Cost Code and Work Section labels. Future integrations must use deterministic `(source_type, source_id, source_line_id)` identities to prevent double counting.

Payments do not create project costs. Future subcontract certificate items and supplier invoice lines may feed this ledger; payments remain settlement only. Future Cost Plus uses approved costs. Corrections will use a dedicated future reversal mechanism.
