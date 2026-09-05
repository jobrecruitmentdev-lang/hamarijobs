@echo off
title HamariJobs Government Recruitment Web Portal (Port 8080)
cd /d "%~dp0"

echo ================================================================
echo    HAMARIJOBS - GOVERNMENT RECRUITMENT PORTAL
echo ================================================================
echo.
echo [INFO] Starting Live Web Server on:
echo        - User Portal:  http://localhost:8080/
echo        - Admin Panel:  http://localhost:8080/admin
echo.
echo [INFO] Binding to all interfaces (0.0.0.0:8080)...
echo.

if exist "C:\xampp\php\php.exe" (
    "C:\xampp\php\php.exe" -S 0.0.0.0:8080 -t backend/public backend/public/index.php
) else (
    php -S 0.0.0.0:8080 -t backend/public backend/public/index.php
)

pause
