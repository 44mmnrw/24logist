@echo off
setlocal EnableExtensions
title Deploy 24logist.ru
REM Usage: deploy.bat "commit message"
REM   1) git add + commit + push to GitHub
REM   2) on server: git pull, composer, npm build, migrate, cache

cd /d "%~dp0.."
if errorlevel 1 (echo ERROR: project folder & pause & exit /b 1)

set SSH=logist_sys@24logist.ru
set REMOTE=/var/www/logist_sys/data/24logistru
set RSCRIPT=%REMOTE%/script_ai/deploy-production.sh
set BRANCH=main
set MSG=%~1
if "%MSG%"=="" set MSG=deploy %date% %time%

where git >nul 2>&1 || (echo ERROR: git not found & pause & exit /b 1)
where ssh >nul 2>&1 || (echo ERROR: ssh not found & pause & exit /b 1)

echo.
echo === 1/4 GIT: commit ===
git add -A
git diff --cached --quiet
if errorlevel 1 (
    git commit -m "%MSG%"
    if errorlevel 1 goto fail
) else (
    echo Nothing to commit, skip.
)

echo.
echo === 2/4 GIT: push origin %BRANCH% ===
git push origin %BRANCH%
if errorlevel 1 goto fail

echo.
echo === 3/4 SERVER: upload deploy script ===
ssh -o BatchMode=yes %SSH% "mkdir -p %REMOTE%/script_ai" || goto fail
scp -o BatchMode=yes script_ai\deploy-production.sh %SSH%:%RSCRIPT% || goto fail
ssh -o BatchMode=yes %SSH% "sed -i 's/\r$//' %RSCRIPT%" || goto fail

echo.
echo === 4/4 SERVER: pull, build, migrate, cache ===
ssh -o BatchMode=yes %SSH% "DEPLOY_APP_DIR=%REMOTE% DEPLOY_BRANCH=%BRANCH% bash %RSCRIPT%" || goto fail

echo.
echo OK https://24logist.ru
pause
exit /b 0

:fail
echo.
echo FAILED
pause
exit /b 1
