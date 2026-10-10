@echo off
cd /d c:\xammpp82\htdocs\albarekaaaaaa
git add app/Livewire/Admin/Manufacturing/ManufacturingUserDashboard.php
git commit -m "fix: wrap manufacturing product save and material deductions in database transaction"
git log -1 --oneline
