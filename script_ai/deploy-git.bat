@echo off
setlocal EnableExtensions
title Git push 24logist.ru
REM Only git — Kaspersky usually does not block this.
REM Usage: deploy-git.bat "commit message"

cd /d "%~dp0.."
if errorlevel 1 (echo ERROR & pause & exit /b 1)

set BRANCH=main
set MSG=%~1
if "%MSG%"=="" set MSG=deploy %date% %time%

where git >nul 2>&1 || (echo ERROR: git not found & pause & exit /b 1)

echo === git add ===
git add -A

echo === git commit ===
git diff --cached --quiet
if errorlevel 1 (
    git commit -m "%MSG%"
    if errorlevel 1 goto fail
) else (
    echo nothing to commit
)

echo === git push ===
git push origin %BRANCH%
if errorlevel 1 goto fail

echo.
echo GIT OK. Now deploy on server — see script_ai\SERVER-DEPLOY.txt
pause
exit /b 0

:fail
echo FAILED
pause
exit /b 1
