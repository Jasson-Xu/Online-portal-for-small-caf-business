@echo off
setlocal
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\setup-xampp.ps1"
if errorlevel 1 (
  echo.
  echo Setup failed. Check that MySQL is running in the XAMPP Control Panel.
  pause
  exit /b 1
)
echo.
echo Press any key to close this window.
pause >nul
