@echo off
echo ==========================================
echo   CarelioEMR Platform - Server Startup
echo ==========================================
echo.

echo Starting Laravel Application Server (port 8000)...
start "CarelioEMR Laravel Server" /D "%~dp0" cmd /k "php artisan serve --host=127.0.0.1 --port=8000"

timeout /t 2 /nobreak >nul

echo.
echo ==========================================
echo   Server is now running!
echo.
echo   SaaS Landing Page:  http://localhost:8000
echo   Admin Portal:       http://localhost:8000/admin/login
echo   API Endpoints:      http://localhost:8000/api
echo ==========================================
pause
