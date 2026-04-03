@echo off
REM Long-running scheduler (equivalent to: php artisan schedule:work)
REM Use for local dev on Windows when you cannot use Task Scheduler.

setlocal
cd /d "%~dp0.."
where php >nul 2>&1
if errorlevel 1 (
  echo PHP is not in PATH. Add PHP to PATH or edit this script to use the full path to php.exe.
  exit /b 1
)
php artisan schedule:work
endlocal
