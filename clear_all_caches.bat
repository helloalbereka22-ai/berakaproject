@echo off
REM Clear all PHP and Laravel caches
cd /d c:\xammpp82\htdocs\albarekaaaaaa

echo.
echo ========================================
echo Clearing All Caches
echo ========================================
echo.

echo 1. Clearing Laravel views cache...
if exist storage\framework\views rmdir /s /q storage\framework\views
mkdir storage\framework\views

echo 2. Clearing Laravel bootstrap cache...
if exist bootstrap\cache rmdir /s /q bootstrap\cache
mkdir bootstrap\cache

echo 3. Clearing Laravel application cache...
if exist storage\framework\cache rmdir /s /q storage\framework\cache
mkdir storage\framework\cache

echo 4. Clearing Laravel sessions...
if exist storage\framework\sessions rmdir /s /q storage\framework\sessions
mkdir storage\framework\sessions

echo.
echo ========================================
echo Cache cleared successfully!
echo ========================================
echo.
echo Now reload your browser with Ctrl+Shift+Delete (clear all)
echo Then refresh the page with Ctrl+Shift+R
echo.
pause
