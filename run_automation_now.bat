@echo off
chcp 65001 >nul
title HamariJobs - Instant Live Ingestion and AI Pipeline
cd /d "%~dp0"

echo ===============================================================================
echo   HAMARIJOBS: INSTANT LIVE INGESTION AND AI EXTRACTION PIPELINE
echo ===============================================================================
echo [1/3] Detecting Python runtime environment...

set "PYTHON_BIN=python"
if exist "%~dp0venv\Scripts\python.exe" set "PYTHON_BIN=%~dp0venv\Scripts\python.exe"

echo [2/3] Using Python: %PYTHON_BIN%
echo [3/3] Launching Live Ingestion Pipeline...
echo -------------------------------------------------------------------------------
echo.

"%PYTHON_BIN%" "%~dp0automation\live_ingestion_pipeline.py"

set EXIT_CODE=%ERRORLEVEL%
echo.
echo -------------------------------------------------------------------------------
if "%EXIT_CODE%"=="0" (
    echo [SUCCESS] Pipeline execution finished successfully!
) else (
    echo [ERROR] Pipeline exited with error code: %EXIT_CODE%. Check output above.
)
echo ===============================================================================
echo Press any key to close this console window...
pause >nul
