@echo off
echo ==========================================
echo   AuraEMR Platform - Server Startup
echo ==========================================
echo.

echo [1/2] Starting Laravel API Backend (port 8000)...
start "AuraEMR Laravel Backend" /D "%~dp0backend" cmd /k "php artisan serve --host=127.0.0.1 --port=8000"

timeout /t 3 /nobreak >nul

echo [2/2] Starting React Frontend (port 3000)...
start "AuraEMR React Frontend" /D "%~dp0" cmd /k "npm run dev"

timeout /t 2 /nobreak >nul

echo.
echo ==========================================
echo   Both servers are now running!
echo.
echo   React Frontend:     http://localhost:3000
echo   Laravel API / Web:  http://localhost:8000
echo   Admin Portal:       http://localhost:8000/admin/login
echo ==========================================
pause
