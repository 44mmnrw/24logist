@echo off
REM Local git only (no ssh/scp — Kaspersky-friendly).
call "%~dp0deploy-git.bat" %*
