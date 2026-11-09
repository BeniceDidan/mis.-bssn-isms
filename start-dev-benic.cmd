@echo off
setlocal
title BSSN ISMS - Dev

REM Peluncur untuk PC ini (benic / ASRock B760M), dibuat 2026-09-08.
REM
REM start-dev.bat yang asli ditulis untuk mesin lain: jalurnya menunjuk ke
REM C:\Users\user\... dan PostgreSQL 18 di port 5433. Keduanya tidak ada di
REM sini, jadi berkas itu dibiarkan apa adanya sebagai catatan sejarah dan
REM peluncur ini dipakai sebagai gantinya.
REM
REM Basis datanya PostgreSQL 16 lokal (database bssn_isms, port 5432) -
REM aplikasi ini TIDAK bisa jalan di SQLite (migrasi memakai indeks GIN).
REM Cluster Postgres itu dipakai bersama BAKOEL/BioVerse, jadi dinyalakan
REM lewat skrip milik BioVerse yang sudah menangani postmaster.pid basi.
REM
REM Fitur AI (Bukti Dukung, Tanya Dokumen, triase Helpdesk) memakai Ollama
REM di 127.0.0.1:11434. Bila Ollama mati, fitur tetap jalan dengan mesin
REM aturan / pencarian kata kunci.

set PROJECT_DIR=C:\Users\benic\mis-bssn-isms
set PHP_DIR=C:\Users\benic\AppData\Local\Programs\php-8.4
set NODE_DIR=C:\Users\benic\AppData\Local\Programs\nodejs-22.14.0
set PATH=%PHP_DIR%;%NODE_DIR%;%PATH%

echo ============================================
echo   BSSN ISMS - Menyalakan Lingkungan Dev
echo ============================================
echo.

if not exist "%PROJECT_DIR%\vendor\autoload.php" (
    echo  Dependensi PHP belum terpasang. Jalankan dulu:
    echo     cd /d "%PROJECT_DIR%"
    echo     php "C:\Users\benic\AppData\Local\Programs\composer\composer.phar" install
    echo.
    pause
    exit /b 1
)

echo [0/2] Memastikan PostgreSQL menyala...
call "C:\Users\benic\bioverse bajol\start-bioverse-pg.cmd"

echo [1/2] Menyalakan server Laravel...
start "BSSN ISMS - Laravel" /D "%PROJECT_DIR%" cmd /k php artisan serve

echo [2/2] Menyalakan Vite (hot-reload CSS/JS)...
start "BSSN ISMS - Vite" /D "%PROJECT_DIR%" cmd /k npm run dev

timeout /t 5 /nobreak >nul
start http://localhost:8000

echo.
echo Dua jendela terbuka: Laravel dan Vite.
echo Tutup masing-masing untuk mematikan service-nya.
echo.
echo CATATAN: kalau halaman tampil tanpa gaya sama sekali, biasanya ada
echo proses Vite lama yang nyangkut dan meninggalkan berkas public\hot.
echo Tutup semua jendela Vite, hapus public\hot, lalu nyalakan lagi.
echo.
pause
