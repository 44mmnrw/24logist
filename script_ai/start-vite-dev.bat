@echo off
setlocal EnableExtensions

chcp 65001 >nul 2>&1
title Vite Dev - 24logistru

cd /d "%~dp0.."
if errorlevel 1 (
    echo [ERROR] Не удалось перейти в папку проекта.
    pause
    exit /b 1
)

if not defined LARAGON_ROOT set "LARAGON_ROOT=C:\laragon"
if exist "%LARAGON_ROOT%\bin\nodejs\node-v24\npm.cmd" (
    set "PATH=%LARAGON_ROOT%\bin\nodejs\node-v24;%PATH%"
) else if exist "%LARAGON_ROOT%\bin\nodejs\node-v22\npm.cmd" (
    set "PATH=%LARAGON_ROOT%\bin\nodejs\node-v22;%PATH%"
)

where npm >nul 2>&1
if errorlevel 1 (
    echo [ERROR] npm не найден. Установите Node.js или добавьте его в PATH.
    echo Проверено: %LARAGON_ROOT%\bin\nodejs\
    pause
    exit /b 1
)

if not exist "package.json" (
    echo [ERROR] package.json не найден в %CD%
    pause
    exit /b 1
)

if not exist "node_modules\" (
    echo.
    echo Установка зависимостей...
    call npm install
    if errorlevel 1 (
        echo [ERROR] npm install завершился с ошибкой.
        pause
        exit /b 1
    )
)

echo.
echo Запуск Vite dev-сервера для 24logistru...
echo Проект: %CD%
echo Остановка: Ctrl+C
echo.

call npm run dev
set "EXIT_CODE=%ERRORLEVEL%"

if not "%EXIT_CODE%"=="0" (
    echo.
    echo [ERROR] Vite завершился с кодом %EXIT_CODE%
)

pause
exit /b %EXIT_CODE%
