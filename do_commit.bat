@echo off
cd /d "c:\xammpp82\htdocs\albarekaaaaaa"
git add app/Livewire/Admin/Manufacturing/ManufacturingUserDashboard.php
git commit -m "fix: correct branch filter logic in stock checks

- Cast filterBranchId to int and compare > 0 to avoid falsy string '0' evaluating false
- Fixes branch filter bypass where string '0' causes queries to include all branches
- Applied to bottle, packing, and seed stock availability checks in addSalesItem()
- Prevents stock deductions from unintended branches and ensures branch-specific stock validation"
echo Commit completed
