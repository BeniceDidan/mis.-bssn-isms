@echo off
setlocal

set PROJECT_DIR=C:\Users\user\Projects\kominfo-isms-app
set PGDATA=C:\Users\user\pgdata
set PG_BIN=C:\Program Files\PostgreSQL\18\bin
set PHP_DIR=C:\Users\user\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe
set NODE_DIR=C:\Users\user\AppData\Local\Programs\nodejs-portable\node-v22.14.0-win-x64

REM Set PATH explicitly for this script and everything it launches, in case
REM Windows Explorer hasn't picked up the PATH changes yet (happens after
REM they were set but before a logout/login).
set PATH=%PHP_DIR%;%NODE_DIR%;C:\Users\user\bin;%PG_BIN%;%PATH%

echo ============================================
echo   Kominfo ISMS - Starting Dev Environment
echo ============================================
echo.

REM --- 1. PostgreSQL (skip if already running on 5433) ---
netstat -ano | findstr ":5433" | findstr "LISTENING" >nul 2>&1
if %errorlevel%==0 (
    echo [1/3] PostgreSQL sudah berjalan di port 5433, dilewati.
) else (
    echo [1/3] Menyalakan PostgreSQL...
    start "Kominfo ISMS - PostgreSQL" /min "%PG_BIN%\postgres.exe" -D "%PGDATA%" -p 5433
    timeout /t 5 /nobreak >nul
)

REM --- 2. Laravel dev server ---
REM /D sets the child's working directory, avoiding the need to nest a
REM "cd /d" (with its own quotes) inside the already-quoted cmd /k string.
echo [2/3] Menyalakan server Laravel (php artisan serve)...
start "Kominfo ISMS - Laravel" /D "%PROJECT_DIR%" cmd /k php artisan serve

REM --- 3. Vite dev server (hot-reload untuk CSS/JS) ---
echo [3/3] Menyalakan Vite (npm run dev)...
start "Kominfo ISMS - Vite" /D "%PROJECT_DIR%" cmd /k npm run dev

REM --- Buka browser setelah server sempat menyala ---
timeout /t 4 /nobreak >nul
start http://localhost:8000

echo.
echo Semua service sudah dinyalakan di jendela terpisah:
echo   - Kominfo ISMS - PostgreSQL
echo   - Kominfo ISMS - Laravel
echo   - Kominfo ISMS - Vite
echo.
echo Tutup jendela masing-masing untuk mematikan service tersebut.
echo Jendela ini boleh ditutup kapan saja, tidak memengaruhi service yang sudah jalan.
echo.
pause
