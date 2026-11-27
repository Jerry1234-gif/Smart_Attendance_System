@echo off
setlocal
set "PHP_EXE=C:\xampp\php\php.exe"
if not exist "%PHP_EXE%" set "PHP_EXE=php"
where php >nul 2>nul
if errorlevel 1 if not exist "C:\xampp\php\php.exe" (
  echo PHP was not found. Install XAMPP or add PHP to PATH.
  pause
  exit /b 1
)
set "FAILED=0"
for /r %%F in (*.php) do (
  "%PHP_EXE%" -l "%%F"
  if errorlevel 1 set "FAILED=1"
)
if "%FAILED%"=="0" (echo All PHP files passed syntax checks.) else (echo One or more PHP files contain syntax errors.)
pause
