# DEMO / UAT

Run only against the dedicated test database:

```powershell
$env:APP_ENV="testing"
$env:DB_DATABASE="hycacmvp_contracting_erp_test"
php database/seed-demo.php
```

Remove only records whose business identifiers start with `DEMO-`:

```powershell
php database/seed-demo.php --reset
```

Suggested checks:

- Browse clients, vendors, projects, contract pricing methods, variations, and approved BOQs.
- Verify the BOQ subcontract value is 254,000, earned value is 190,500 (75%), posted payments are 180,000, and available payment is 10,500.
- Verify the lump-sum subcontract value is 100,000, approved progress is 20%, posted payments are 15,000, and available payment is 5,000.
- Verify draft payments and costs do not affect approved totals.
- Verify the approved client statement is 92,000, allocated receipts are 70,000, statement outstanding is 22,000, and the posted receipt has 30,000 unallocated.
- Attempt edits/cancellation on posted financial records and confirm rejection.
