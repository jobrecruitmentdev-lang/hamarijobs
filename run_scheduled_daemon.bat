@echo off
chcp 65001 >nul
title HamariJobs - Background Scheduled Automation Daemon
cd /d "%~dp0"

echo ===============================================================================
echo   HAMARIJOBS: AUTONOMOUS BACKGROUND SCHEDULER DAEMON
echo ===============================================================================

set "PYTHON_BIN=python"
if exist "%~dp0venv\Scripts\python.exe" set "PYTHON_BIN=%~dp0venv\Scripts\python.exe"

echo [INFO] Starting Autonomous Background Daemon...
"%PYTHON_BIN%" "%~dp0automation\scheduler\cron.py" --start

echo.
echo [CURRENT DAEMON STATUS]
"%PYTHON_BIN%" "%~dp0automation\scheduler\cron.py" --status

echo.
echo ===============================================================================
echo  The scheduler daemon runs in the background and auto-crawls every 4 hours.
echo  Logs are written to: storage\daemon.log
echo ===============================================================================
echo Press any key to close this launcher window...
pause >nul
