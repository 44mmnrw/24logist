#Requires -Version 5.1
<#
.SYNOPSIS
    Deploy 24logistru to production (git pull on server only).

.EXAMPLE
    .\script_ai\deploy.ps1
    .\script_ai\deploy.ps1 -Push
#>
[CmdletBinding()]
param(
    [switch]$Push,
    [switch]$Maintenance,
    [string]$Branch = '',
    [string]$SshHost = '',
    [string]$SshUser = '',
    [string]$RemoteAppDir = ''
)

$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'

$ScriptDir = $PSScriptRoot
$ProjectRoot = (Resolve-Path (Join-Path $ScriptDir '..')).Path

$config = @{
    SshHost      = '24logist.ru'
    SshUser      = 'logist_sys'
    RemoteWebDir = '/var/www/logist_sys/data/www/24logist.ru'
    RemoteAppDir = '/var/www/logist_sys/data/www/24logist.ru/.app'
    GitBranch    = 'main'
}

$localConfig = Join-Path $ScriptDir 'deploy.local.ps1'
if (Test-Path $localConfig) {
    $loaded = & $localConfig
    if ($loaded -is [hashtable]) {
        foreach ($key in $loaded.Keys) {
            $config[$key] = $loaded[$key]
        }
    }
}

if ($Branch) { $config.GitBranch = $Branch }
if ($SshHost) { $config.SshHost = $SshHost }
if ($SshUser) { $config.SshUser = $SshUser }
if ($RemoteAppDir) { $config.RemoteAppDir = $RemoteAppDir }

$sshTarget = '{0}@{1}' -f $config.SshUser, $config.SshHost
$remoteDir = $config.RemoteAppDir.TrimEnd('/')
$branch = $config.GitBranch

function Write-Step([string]$Message) {
    Write-Host ''
    Write-Host "==> $Message" -ForegroundColor Cyan
}

Set-Location $ProjectRoot

if (-not (Get-Command ssh -ErrorAction SilentlyContinue)) {
    throw 'Command not found: ssh'
}

Write-Step "SSH check ($sshTarget)"
ssh -o BatchMode=yes -o ConnectTimeout=15 $sshTarget 'echo OK' | Out-Null

if ($Push) {
    Write-Step "git push origin $branch"
    git push origin $branch
}

Write-Step 'Upload deploy-production.sh'
$remoteScript = "$remoteDir/script_ai/deploy-production.sh"
$localScript = Join-Path $ScriptDir 'deploy-production.sh'

ssh -o BatchMode=yes $sshTarget "mkdir -p $remoteDir/script_ai"
scp -o BatchMode=yes $localScript "${sshTarget}:${remoteScript}"
ssh -o BatchMode=yes $sshTarget "sed -i 's/\r$//' $remoteScript"

$maintFlag = if ($Maintenance) { '1' } else { '0' }
$remoteWeb = $config.RemoteWebDir.TrimEnd('/')
$remoteCmd = "DEPLOY_WEB_DIR='$remoteWeb' DEPLOY_APP_DIR='$remoteDir' DEPLOY_BRANCH='$branch' DEPLOY_MAINTENANCE='$maintFlag' bash $remoteScript"

Write-Step 'Remote deploy'
& ssh -o BatchMode=yes $sshTarget $remoteCmd 2>&1 | ForEach-Object { Write-Host $_ }
if ($LASTEXITCODE -ne 0) { throw "Remote deploy failed (exit $LASTEXITCODE)" }

Write-Host ''
Write-Host 'Deploy done: https://24logist.ru' -ForegroundColor Green
