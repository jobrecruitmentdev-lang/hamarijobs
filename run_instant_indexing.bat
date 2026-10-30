@echo off
chcp 65001 >nul
title HamariJobs - Instant Multi-Key Google & Search Indexing Engine
cd /d "%~dp0"

echo ===============================================================================
echo   🚀 HAMARIJOBS: INSTANT MULTI-KEY SEARCH INDEXING ENGINE
echo   Target: https://hamarijobs.com/
echo ===============================================================================
echo.

set "PYTHON_BIN=python"
if exist "%~dp0venv\Scripts\python.exe" set "PYTHON_BIN=%~dp0venv\Scripts\python.exe"

echo [1/2] Launching Google Web Search Indexing API (Multi-Key Pool)...
echo -------------------------------------------------------------------------------
"%PYTHON_BIN%" "%~dp0automation\seo\google_indexing_submitter.py"

echo.
echo [2/2] Launching Multi-Engine IndexNow Submission (Bing, Yandex, etc.)...
echo -------------------------------------------------------------------------------
"%PYTHON_BIN%" "%~dp0scratch\submit_all_indexnow.py"

echo.
echo ===============================================================================
echo   ✅ Instant Indexing execution completed!
echo ===============================================================================
echo Press any key to exit...
pause >nul
