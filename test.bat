@echo off
cd /d "c:\xammpp82\htdocs\albarekaaaaaa"
php -l app/Livewire/Admin/Manufacturing/ManufacturingUserDashboard.php
if %ERRORLEVEL% EQU 0 (
    echo Syntax check passed
    git add app/Livewire/Admin/Manufacturing/ManufacturingUserDashboard.php
    git commit -m "fix: correct branch filter logic in stock checks

- Cast filterBranchId to int and compare > 0 to avoid falsy string '0' evaluating to false
- Fixes branch filter bypass where string '0' causes queries to include all branches
- Applied to bottle, packing, and seed stock availability checks
- Prevents stock deductions from unintended branches"
) else (
    echo Syntax error detected
    exit /b 1
)
