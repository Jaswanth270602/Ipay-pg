@echo off
REM Run once per minute via Windows Task Scheduler (same as Linux cron + schedule:run).
REM Action: Start a program -> %PROJECT%\scripts\schedule-run.bat
REM Trigger: Daily, repeat every 1 minute for 1 day (or "At startup" + repeat - not ideal; use "One time" + repeat every 1 min).

setlocal
cd /d "%~dp0.."
where php >nul 2>&1
if errorlevel 1 (
  echo PHP is not in PATH. Add PHP to PATH or edit this script to use the full path to php.exe.
  exit /b 1
)
php artisan schedule:run >> storage\logs\scheduler.log 2>&1
endlocal
