@echo off
setlocal EnableExtensions EnableDelayedExpansion
chcp 65001 >nul
title 24logist.ru - Deploy

call :fn_init_colors

REM ================================================================
REM  CONFIG
REM ================================================================

set "SERVER_HOST=24logist.ru"
set "SERVER_USER=logist_sys"
set "SERVER_PATH=/var/www/logist_sys/data/24logistru"
set "GIT_BRANCH=main"
set "GIT_REMOTE=origin"
set "GIT_REPO_SSH_URL=git@github.com:44mmnrw/24logist.git"
REM Server clone uses HTTPS; leave empty unless deploy key is configured on server
set "SERVER_GIT_SSH_COMMAND="

set "BUILD_NODE_MAX_OLD_SPACE=512"
set "BUILD_TIMEOUT_MINUTES=20"
set "BUILD_NPM_CI_TIMEOUT_MINUTES=25"
set "SSH_RETRY_ATTEMPTS=3"
set "SSH_RETRY_DELAY_SEC=2"

set "DEFAULT_ACTION=1"
set "AUTO_MODE=0"
set "AUTO_COMMIT=y"
set "AUTO_COMMIT_MSG=AutoDeploy"
set "NONINTERACTIVE=0"
set "SKIP_FRONTEND_BUILD=0"

set "SSH_KEY_PATH=%USERPROFILE%\.ssh\id_ed25519_work_prod_2026"
set "SSH_KEY_PASSPHRASE=wwwdev"

set "GIT_SSH_KEY_PATH=%USERPROFILE%\.ssh\id_ed25519_work_prod_2026"
set "GIT_SSH_KEY_PASSPHRASE=wwwdev"
set "LOCAL_GIT_SSH_COMMAND="

if defined DEPLOY_SOURCE_DIR (
	for %%I in ("%DEPLOY_SOURCE_DIR%..") do set "LOCAL_PROJECT=%%~fI"
) else (
	for %%I in ("%~dp0..") do set "LOCAL_PROJECT=%%~fI"
)

if not defined DEPLOY_RUN_FROM_TEMP (
	set "DEPLOY_RUN_FROM_TEMP=1"
	set "DEPLOY_SOURCE_DIR=%~dp0"
	set "_DEPLOY_TMP=%TEMP%\24logist_deploy_runner_%RANDOM%%RANDOM%.bat"
	set "_DEPLOY_SHELL_PAUSE=1"
	for %%A in (%*) do if /I "%%~A"=="--auto" set "_DEPLOY_SHELL_PAUSE=0"
	copy /y "%~f0" "!_DEPLOY_TMP!" >nul
	if errorlevel 1 (
		echo [ERROR] Failed to create temp deploy runner.
		exit /b 1
	)
	call "!_DEPLOY_TMP!" %*
	set "_DEPLOY_RC=!errorlevel!"
	del "!_DEPLOY_TMP!" >nul 2>&1
	if "!_DEPLOY_SHELL_PAUSE!"=="1" (
		echo.
		echo ==============================================
		if "!_DEPLOY_RC!"=="0" (
			echo   [OK] Готово. Деплой завершён успешно.
		) else (
			echo   [ERROR] Скрипт завершился с ошибкой.
			echo   Код выхода: !_DEPLOY_RC!
		)
		echo ==============================================
		echo.
		echo Нажмите любую клавишу, чтобы закрыть это окно...
		pause ^>nul
	)
	exit /b %_DEPLOY_RC%
)

REM ================================================================
REM  ARGUMENTS
REM ================================================================

if /I "%~1"=="--help" goto :help
if /I "%~1"=="-h" goto :help
if /I "%~1"=="--skip-build" (
	set "SKIP_FRONTEND_BUILD=1"
	shift
)

if /I "%~1"=="--auto" (
	set "AUTO_MODE=1"
	set "NONINTERACTIVE=1"
	set "CHOICE=%DEFAULT_ACTION%"

	if not "%~2"=="" (
		if "%~2"=="1" set "CHOICE=1"
		if "%~2"=="0" set "CHOICE=0"
		if /I "%~2"=="y" set "AUTO_COMMIT=y"
		if /I "%~2"=="n" set "AUTO_COMMIT=n"
	)

	if not "%~3"=="" set "AUTO_COMMIT=%~3"
	if not "%~4"=="" set "AUTO_COMMIT_MSG=%~4"
)

if "%~1"=="1" (
	set "CHOICE=1"
	set "NONINTERACTIVE=0"
)
if "%~1"=="0" (
	set "CHOICE=0"
	set "NONINTERACTIVE=1"
)

call :fn_resolve_ssh_paths
call :fn_build_local_git_ssh_command

REM ================================================================
REM  MENU
REM ================================================================

if not defined CHOICE (
	cls
	echo.
	echo %C_CYAN%==============================================%C_RESET%
	echo %C_BOLD%%C_WHITE%  24logist.ru - Deploy%C_RESET%
	echo %C_DIM%  Server : %C_YELLOW%%SERVER_HOST%%C_RESET%
	echo %C_DIM%  Path   : %C_YELLOW%%SERVER_PATH%%C_RESET%
	echo %C_DIM%  Branch : %C_GREEN%%GIT_BRANCH%%C_RESET%
	echo %C_CYAN%==============================================%C_RESET%
	echo.
	echo %C_GREEN%  1.%C_RESET% %C_WHITE%Deploy%C_RESET%  %C_DIM%^(commit, push, pull, composer, migrate, build, cache^)%C_RESET%
	echo %C_RED%  0.%C_RESET% %C_WHITE%Exit%C_RESET%
	echo.
	set /p "CHOICE=%C_BOLD%%C_WHITE%Select [0-1]: %C_RESET%"
)

if defined CHOICE (
	for /f "tokens=* delims= " %%C in ("!CHOICE!") do set "CHOICE=%%C"
)

if "!CHOICE!"=="1" goto :fn_deploy_code
if "!CHOICE!"=="0" goto :done

echo.
echo [ERROR] Invalid choice. Use 0 or 1.
goto :done


REM ================================================================
REM  DEPLOY CODE
REM ================================================================
:fn_deploy_code
echo.
echo ---- DEPLOY 24logist.ru ----------------------------------------
cd /d "%LOCAL_PROJECT%"

echo [0] Unlock git SSH key...
call :fn_unlock_git_ssh_key
if errorlevel 1 (
	echo [ERROR] Failed to unlock local git SSH key.
	exit /b 1
)

echo [0.1] Ensure git remote URL...
for /f "delims=" %%R in ('git remote get-url %GIT_REMOTE% 2^>nul') do set "CURRENT_REMOTE=%%R"
if not defined CURRENT_REMOTE (
	echo [ERROR] Git remote "%GIT_REMOTE%" not found.
	exit /b 1
)
if /I not "!CURRENT_REMOTE!"=="%GIT_REPO_SSH_URL%" (
	echo [INFO] Update "%GIT_REMOTE%" to %GIT_REPO_SSH_URL%
	git remote set-url %GIT_REMOTE% %GIT_REPO_SSH_URL%
	if errorlevel 1 exit /b 1
)

echo [1] Local branch %GIT_BRANCH%...
call :fn_checkout_local_branch "%GIT_BRANCH%"
if errorlevel 1 exit /b 1

call :fn_sync_local_branch_with_remote "%GIT_BRANCH%" "%GIT_REMOTE%"
if errorlevel 1 exit /b 1

set "_HAS_CHANGES=0"
git status --short > "%TEMP%\git_status.tmp" 2>&1
set "_size=0"
if exist "%TEMP%\git_status.tmp" for %%A in ("%TEMP%\git_status.tmp") do set "_size=%%~zA"
del "%TEMP%\git_status.tmp" 2>nul
if %_size% gtr 0 set "_HAS_CHANGES=1"

if "%_HAS_CHANGES%"=="1" (
	echo.
	echo Uncommitted changes:
	git status --short
	echo.
	if "%NONINTERACTIVE%"=="1" (
		set "COMMIT_CHOICE=%AUTO_COMMIT%"
		echo [AUTO] Commit: !COMMIT_CHOICE!
	) else (
		set /p "COMMIT_CHOICE=Commit and push? ^(y/n^): "
	)
	if /I "!COMMIT_CHOICE!"=="y" (
		if "%NONINTERACTIVE%"=="1" (
			set "COMMIT_MSG=%AUTO_COMMIT_MSG%"
		) else (
			set /p "COMMIT_MSG=Commit message ^(Enter = deploy: update^): "
		)
		if "!COMMIT_MSG!"=="" set "COMMIT_MSG=deploy: update"
		git add -A
		if errorlevel 1 goto :after_local_commit_block
		git diff --cached --quiet
		if not errorlevel 1 (
			echo [WARN] Nothing staged. Deploy current HEAD.
		) else (
			git commit -m "!COMMIT_MSG!"
			if errorlevel 1 (
				if "%NONINTERACTIVE%"=="1" goto :after_local_commit_block
				set /p "CONTINUE_WITHOUT_COMMIT=Continue without commit? ^(y/n^): "
				if /I not "!CONTINUE_WITHOUT_COMMIT!"=="y" exit /b 1
				goto :after_local_commit_block
			)
			call :fn_run_git_push %GIT_REMOTE% %GIT_BRANCH%
			if errorlevel 1 exit /b 1
			echo [OK] Pushed to GitHub.
		)
	) else (
		echo [INFO] Skip commit. Deploy current HEAD.
	)
) else (
	echo [OK] Working tree clean on %GIT_BRANCH%.
)
:after_local_commit_block

echo.
echo [0.5] Unlock server SSH key...
call :fn_unlock_ssh_key
if errorlevel 1 exit /b 1

echo.
echo [2] git pull on server...
if defined SERVER_GIT_SSH_COMMAND (
	call :fn_run_ssh "%SERVER_USER%@%SERVER_HOST%" "cd %SERVER_PATH% && git stash push -u -m deploy-auto-stash >/dev/null 2>&1 || true; GIT_SSH_COMMAND='%SERVER_GIT_SSH_COMMAND%' git pull --ff-only %GIT_REMOTE% %GIT_BRANCH%"
) else (
	call :fn_run_ssh "%SERVER_USER%@%SERVER_HOST%" "cd %SERVER_PATH% && git stash push -u -m deploy-auto-stash >/dev/null 2>&1 || true; git pull --ff-only %GIT_REMOTE% %GIT_BRANCH%"
)
if errorlevel 1 (
	echo [ERROR] git pull failed on server.
	exit /b 1
)
echo [OK] Server code updated.

echo.
echo [3] composer install on server...
call :fn_run_ssh "%SERVER_USER%@%SERVER_HOST%" "cd %SERVER_PATH% && php composer.phar install --no-dev --optimize-autoloader --no-interaction"
if not errorlevel 1 (
	call :fn_run_ssh "%SERVER_USER%@%SERVER_HOST%" "cd %SERVER_PATH% && sed -i 's/\r$//' script_ai/patch-livewire-upload.sh && bash script_ai/patch-livewire-upload.sh"
)
if errorlevel 1 (
	call :fn_run_ssh "%SERVER_USER%@%SERVER_HOST%" "cd %SERVER_PATH% && composer install --no-dev --optimize-autoloader --no-interaction"
	if errorlevel 1 (
		echo [ERROR] composer install failed.
		exit /b 1
	)
)

echo.
echo [4] php artisan migrate on server...
call :fn_run_ssh "%SERVER_USER%@%SERVER_HOST%" "cd %SERVER_PATH% && php artisan migrate --force --no-interaction -v"
if errorlevel 1 (
	echo [ERROR] migrate failed on server.
	echo [HINT] ssh %SERVER_USER%@%SERVER_HOST% "cd %SERVER_PATH% && php artisan migrate --force -v"
	exit /b 1
)
echo [OK] Migrations applied.

echo.
echo [4.9] Ensure Node.js on server ^($HOME/opt/node^)...
call :fn_run_ssh "%SERVER_USER%@%SERVER_HOST%" "test -x $HOME/opt/node/bin/node || (test -f %SERVER_PATH%/script_ai/install-node-server.sh && sed -i 's/\r$//' %SERVER_PATH%/script_ai/install-node-server.sh && bash %SERVER_PATH%/script_ai/install-node-server.sh) || (echo [ERROR] Node missing. Install: bash script_ai/install-node-server.sh && exit 1)"
if errorlevel 1 exit /b 1

echo.
echo [5] Frontend build on server...
if "%SKIP_FRONTEND_BUILD%"=="1" (
	echo [WARN] Skipped ^(--skip-build^).
) else (
	call :fn_run_ssh "%SERVER_USER%@%SERVER_HOST%" "cd %SERVER_PATH% && chmod +x script_ai/remote_frontend_build.sh && BUILD_NODE_MAX_OLD_SPACE=%BUILD_NODE_MAX_OLD_SPACE% BUILD_TIMEOUT_MINUTES=%BUILD_TIMEOUT_MINUTES% BUILD_NPM_CI_TIMEOUT_MINUTES=%BUILD_NPM_CI_TIMEOUT_MINUTES% bash script_ai/remote_frontend_build.sh"
	if errorlevel 1 (
		echo [ERROR] Frontend build failed.
		echo [HINT] Backend migrated; fix Vite and re-run, or: deploy.bat --skip-build --auto
		exit /b 1
	)
	echo [OK] Frontend built.
)

echo.
echo [6] Laravel cache on server...
REM route:cache breaks Livewire/Filament file uploads (upload-file, preview-file).
call :fn_run_ssh "%SERVER_USER%@%SERVER_HOST%" "cd %SERVER_PATH% && php artisan optimize:clear && php artisan route:clear && php artisan config:clear"
if errorlevel 1 exit /b 1
REM Do not config:cache — breaks Livewire signed URLs / env on this host.
call :fn_run_ssh "%SERVER_USER%@%SERVER_HOST%" "cd %SERVER_PATH% && php artisan view:cache"
if errorlevel 1 exit /b 1
call :fn_run_ssh "%SERVER_USER%@%SERVER_HOST%" "cd %SERVER_PATH% && php artisan storage:link --force && chmod -R ug+rwx storage bootstrap/cache && rm -f storage/app/public/livewire-tmp/*.json"
if errorlevel 1 exit /b 1
call :fn_run_ssh "%SERVER_USER%@%SERVER_HOST%" "cd %SERVER_PATH% && php artisan queue:restart"
if errorlevel 1 exit /b 1

echo.
echo ----------------------------------------------
echo [OK] Деплой завершён: git, composer, migrate, build, cache.
echo ----------------------------------------------
exit /b 0


:done
if "%NONINTERACTIVE%"=="1" (
	endlocal
	exit /b 0
)
endlocal
exit /b 0


:fn_sync_local_branch_with_remote
set "_SYNC_BRANCH=%~1"
set "_SYNC_REMOTE=%~2"
if "%_SYNC_BRANCH%"=="" set "_SYNC_BRANCH=%GIT_BRANCH%"
if "%_SYNC_REMOTE%"=="" set "_SYNC_REMOTE=%GIT_REMOTE%"

echo [INFO] git fetch %_SYNC_REMOTE%/%_SYNC_BRANCH%...
call :fn_run_git_fetch %_SYNC_REMOTE% %_SYNC_BRANCH%
if errorlevel 1 exit /b 1

set "_LOCAL_AHEAD=0"
set "_LOCAL_BEHIND=0"
for /f "tokens=1,2" %%A in ('git rev-list --left-right --count %_SYNC_REMOTE%/%_SYNC_BRANCH%...%_SYNC_BRANCH% 2^>nul') do (
	set "_LOCAL_BEHIND=%%A"
	set "_LOCAL_AHEAD=%%B"
)
if not "%_LOCAL_BEHIND%"=="0" (
	echo [ERROR] Local branch behind remote by %_LOCAL_BEHIND% commit^(s^). Run: git pull --ff-only %_SYNC_REMOTE% %_SYNC_BRANCH%
	exit /b 1
)
if not "%_LOCAL_AHEAD%"=="0" (
	call :fn_run_git_push %_SYNC_REMOTE% %_SYNC_BRANCH%
	if errorlevel 1 exit /b 1
)
exit /b 0


:fn_unlock_ssh_key
call :fn_unlock_specific_ssh_key "%SSH_KEY_PATH%" "%SSH_KEY_PASSPHRASE%" "server"
exit /b %errorlevel%

:fn_unlock_git_ssh_key
call :fn_unlock_specific_ssh_key "%GIT_SSH_KEY_PATH%" "%GIT_SSH_KEY_PASSPHRASE%" "git"
exit /b %errorlevel%

:fn_unlock_specific_ssh_key
set "_KEY_PATH=%~1"
set "_KEY_PASSPHRASE=%~2"
set "_KEY_LABEL=%~3"
if "%_KEY_PATH%"=="" exit /b 0
if not exist "%_KEY_PATH%" (
	echo [WARN] Key not found: %_KEY_PATH%. Use ssh-agent.
	exit /b 0
)
where ssh-add >nul 2>&1
if errorlevel 1 exit /b 0
for %%I in ("%_KEY_PATH%") do set "_KEY_NAME=%%~nxI"
ssh-add -l 2>nul | findstr /I /C:"!_KEY_NAME!" >nul
if not errorlevel 1 exit /b 0
call :fn_prepare_askpass "%_KEY_PASSPHRASE%"
ssh-add "%_KEY_PATH%" <nul >nul 2>&1
set "_SSH_ADD_RC=%errorlevel%"
call :fn_cleanup_askpass
if not "%_SSH_ADD_RC%"=="0" (
	echo [WARN] Could not unlock key ^(!_KEY_LABEL!^). Will try ssh-agent / GIT_SSH_COMMAND.
) else (
	echo [OK] SSH key unlocked ^(!_KEY_LABEL!^).
)
exit /b 0


:fn_init_colors
set "C_RESET="
set "C_BOLD="
set "C_DIM="
set "C_RED="
set "C_GREEN="
set "C_YELLOW="
set "C_CYAN="
set "C_WHITE="
for /f %%E in ('echo prompt $E ^| cmd') do set "ESC=%%E"
if not defined ESC exit /b 0
set "C_RESET=%ESC%[0m"
set "C_BOLD=%ESC%[1m"
set "C_DIM=%ESC%[2m"
set "C_RED=%ESC%[31m"
set "C_GREEN=%ESC%[32m"
set "C_YELLOW=%ESC%[33m"
set "C_CYAN=%ESC%[36m"
set "C_WHITE=%ESC%[97m"
exit /b 0


:fn_prepare_askpass
set "_ASKPASS_PASSPHRASE=%~1"
if "%_ASKPASS_PASSPHRASE%"=="" exit /b 0
set "_ASKPASS_FILE=%TEMP%\24logist_askpass.cmd"
>"%_ASKPASS_FILE%" echo @echo %_ASKPASS_PASSPHRASE%
set "SSH_ASKPASS=%_ASKPASS_FILE%"
set "SSH_ASKPASS_REQUIRE=force"
set "DISPLAY=24logist-deploy"
exit /b 0

:fn_cleanup_askpass
if defined _ASKPASS_FILE del "%_ASKPASS_FILE%" 2>nul
set "_ASKPASS_FILE="
set "SSH_ASKPASS="
set "SSH_ASKPASS_REQUIRE="
set "DISPLAY="
exit /b 0


:fn_checkout_local_branch
set "_TARGET_BRANCH=%~1"
set "_CURRENT_BRANCH="
for /f "delims=" %%B in ('git branch --show-current 2^>nul') do set "_CURRENT_BRANCH=%%B"
if /I "%_CURRENT_BRANCH%"=="%_TARGET_BRANCH%" exit /b 0
git checkout "%_TARGET_BRANCH%"
exit /b %errorlevel%


:fn_run_git_push
call :fn_prepare_askpass "%GIT_SSH_KEY_PASSPHRASE%"
set "_USED_LOCAL_GIT_SSH=0"
if defined LOCAL_GIT_SSH_COMMAND (
	set "GIT_SSH_COMMAND=!LOCAL_GIT_SSH_COMMAND!"
	set "_USED_LOCAL_GIT_SSH=1"
)
git push %* <nul
set "_CMD_RC=%errorlevel%"
if not "%_CMD_RC%"=="0" if "%_USED_LOCAL_GIT_SSH%"=="1" (
	echo [WARN] git push with explicit key failed. Retry with ssh-agent...
	set "GIT_SSH_COMMAND="
	git push %* <nul
	set "_CMD_RC=!errorlevel!"
)
set "GIT_SSH_COMMAND="
set "_USED_LOCAL_GIT_SSH="
call :fn_cleanup_askpass
exit /b %_CMD_RC%

:fn_run_git_fetch
call :fn_prepare_askpass "%GIT_SSH_KEY_PASSPHRASE%"
set "_USED_LOCAL_GIT_SSH=0"
if defined LOCAL_GIT_SSH_COMMAND (
	set "GIT_SSH_COMMAND=!LOCAL_GIT_SSH_COMMAND!"
	set "_USED_LOCAL_GIT_SSH=1"
)
git fetch %* <nul
set "_CMD_RC=%errorlevel%"
if not "%_CMD_RC%"=="0" if "%_USED_LOCAL_GIT_SSH%"=="1" (
	echo [WARN] git fetch with explicit key failed. Retry with ssh-agent...
	set "GIT_SSH_COMMAND="
	git fetch %* <nul
	set "_CMD_RC=!errorlevel!"
)
set "GIT_SSH_COMMAND="
set "_USED_LOCAL_GIT_SSH="
call :fn_cleanup_askpass
if not "%_CMD_RC%"=="0" (
	echo [HINT] Add key: ssh-add "%GIT_SSH_KEY_PATH%"
	echo [HINT] Test: ssh -T git@github.com
)
exit /b %_CMD_RC%


:fn_resolve_ssh_paths
if exist "%SSH_KEY_PATH%" exit /b 0
if defined HOMEDRIVE if defined HOMEPATH (
	if exist "%HOMEDRIVE%%HOMEPATH%\.ssh\id_ed25519_work_prod_2026" (
		set "SSH_KEY_PATH=%HOMEDRIVE%%HOMEPATH%\.ssh\id_ed25519_work_prod_2026"
		set "GIT_SSH_KEY_PATH=%HOMEDRIVE%%HOMEPATH%\.ssh\id_ed25519_work_prod_2026"
	)
)
exit /b 0

:fn_build_local_git_ssh_command
set "LOCAL_GIT_SSH_COMMAND="
if exist "%GIT_SSH_KEY_PATH%" (
	set "_GIT_SSH_KEY_PATH_POSIX=%GIT_SSH_KEY_PATH:\=/%"
	REM Windows OpenSSH: path without quotes (no spaces in USERPROFILE\.ssh\...)
	set "LOCAL_GIT_SSH_COMMAND=ssh -i !_GIT_SSH_KEY_PATH_POSIX! -o IdentitiesOnly=yes -o BatchMode=yes -o ConnectTimeout=15 -o StrictHostKeyChecking=accept-new"
	set "_GIT_SSH_KEY_PATH_POSIX="
)
exit /b 0


:fn_run_ssh
call :fn_prepare_askpass "%SSH_KEY_PASSPHRASE%"
set "_SSH_TARGET=%~1"
set "_SSH_COMMAND=%~2"
set "_SSH_KNOWN_HOSTS_FILE=%TEMP%\24logist_known_hosts"
set "_SSH_OPTS=-A -o ConnectTimeout=15 -o ServerAliveInterval=30 -o ServerAliveCountMax=240 -o StrictHostKeyChecking=accept-new -o UserKnownHostsFile=%_SSH_KNOWN_HOSTS_FILE%"
set "_SSH_MAX_ATTEMPTS=%SSH_RETRY_ATTEMPTS%"
if not defined _SSH_MAX_ATTEMPTS set "_SSH_MAX_ATTEMPTS=1"
set "_SSH_DELAY_SECONDS=%SSH_RETRY_DELAY_SEC%"
set "_SSH_ATTEMPT=1"
if exist "%SSH_KEY_PATH%" (
	set "_SSH_IDENTITY_OPT=-i "%SSH_KEY_PATH%" -o BatchMode=yes"
) else (
	set "_SSH_IDENTITY_OPT=-o BatchMode=no"
)
set "_CMD_RC=1"
:fn_run_ssh_retry_loop
if "%_SSH_COMMAND%"=="" (
	ssh %_SSH_OPTS% %_SSH_IDENTITY_OPT% "%_SSH_TARGET%" <nul
) else (
	ssh %_SSH_OPTS% %_SSH_IDENTITY_OPT% "%_SSH_TARGET%" "%_SSH_COMMAND%" <nul
)
set "_CMD_RC=%errorlevel%"
if "%_CMD_RC%"=="0" goto :fn_run_ssh_finish
if not "%_CMD_RC%"=="255" goto :fn_run_ssh_finish
if %_SSH_ATTEMPT% GEQ %_SSH_MAX_ATTEMPTS% goto :fn_run_ssh_finish
echo [WARN] SSH retry %_SSH_ATTEMPT%/%_SSH_MAX_ATTEMPTS%...
timeout /t %_SSH_DELAY_SECONDS% /nobreak >nul
set /a _SSH_ATTEMPT+=1
goto :fn_run_ssh_retry_loop
:fn_run_ssh_finish
call :fn_cleanup_askpass
exit /b %_CMD_RC%


:help
echo.
echo Usage:
echo   deploy.bat              - menu
echo   deploy.bat 1            - deploy
echo   deploy.bat --auto       - auto: commit y, msg AutoDeploy
echo   deploy.bat --auto 1 n   - deploy without auto-commit
echo   deploy.bat --skip-build - skip npm/vite on server
echo.
echo Steps: commit/push -^> pull -^> composer -^> migrate -^> build -^> cache
echo.
endlocal
exit /b 0
