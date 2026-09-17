# Client Receipts

Client Receipts records cash received from clients and allocates posted cash against approved Client Progress Statements. A receipt is not a receivable, revenue, Project Actual Cost, progress event, or accounting journal. Posting never allocates cash automatically.

Receipts move only from `draft` to `posted` or `cancelled`. Posted receipts are immutable and may remain fully or partly unallocated as client advances. Allocations are positive, append-only settlement rows; later top-ups to the same statement are allowed, while normal edit/delete/cancel operations are not.

Allocation enforces both `receipt allocations <= posted receipt amount` and `statement allocations <= approved current statement amount`. Receipt and statement must belong to the same Client Contract. A receipt posted before a contract is later cancelled may still settle an already-approved statement; receipt/statement dates have no ordering requirement.

The Client Contract row is the shared settlement lock. Lock order is Contract, Receipt, then Statements by ascending ID. All arithmetic and comparison uses database `DECIMAL`; PHP carries money as strings. Paid, allocated, outstanding, and unallocated values are derived and are not stored on progress statements.

Integrity summaries detect over-allocation and cross-contract rows, block new allocation, and require manual investigation. Future extensions may add Receipt Reversal, Allocation Reversal, Refund, and accounting integration without rewriting posted history.
