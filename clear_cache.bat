@echo off
cd /d c:\xammpp82\htdocs\albarekaaaaaa

echo Clearing storage/framework/views...
for /d %%F in (storage\framework\views\*) do rmdir /s /q "%%F"
del /q storage\framework\views\*.*

echo Clearing storage/app/livewire...
if exist storage\app\livewire rmdir /s /q storage\app\livewire

echo Cache cleared!
pause
