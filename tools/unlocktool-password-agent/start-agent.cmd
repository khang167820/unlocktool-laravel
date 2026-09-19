@echo off
title UnlockTool Password Agent - unlocktool.us
cd /d "%~dp0"

echo.
echo  ========================================
echo   UnlockTool Password Rotation Agent
echo   unlocktool.us
echo  ========================================
echo.

:: Check Node.js
where node >nul 2>&1
if %errorlevel% neq 0 (
    echo [LOI] Node.js chua duoc cai dat!
    echo Tai Node.js tai: https://nodejs.org/
    echo.
    pause
    exit /b 1
)

:: Install dependencies if needed
if not exist "node_modules" (
    echo Dang cai dat thu vien...
    call npm install --no-audit --no-fund
    echo.
)

:: Run agent
node unlocktool-password-agent.mjs

:: Keep window open if agent exits
echo.
echo Agent da dung. Nhan phim bat ky de dong.
pause >nul
