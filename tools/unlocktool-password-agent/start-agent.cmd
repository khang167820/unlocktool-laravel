@echo off
chcp 65001 >nul 2>&1
title UnlockTool Password Agent - unlocktool.us
cd /d "%~dp0"

echo.
echo  ========================================
echo   UnlockTool Password Rotation Agent
echo   unlocktool.us
echo  ========================================
echo.

:: Doi mang on dinh 5 giay khi khoi dong cung Windows
timeout /t 5 /nobreak >nul 2>&1

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

:LOOP
node unlocktool-password-agent.mjs

echo.
echo [CANH BAO] Agent bi ngat ket noi hoac da dung.
echo Se tu dong ket noi lai sau 10 giay... (Nhan Ctrl+C neu muon dung han)
timeout /t 10
goto LOOP
