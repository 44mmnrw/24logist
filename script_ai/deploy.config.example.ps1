# Copy to deploy.local.ps1 if needed.

@{
    SshHost      = '24logist.ru'
    SshUser      = 'logist_sys'
    RemoteAppDir = '/var/www/logist_sys/data/24logistru'
    GitBranch    = 'main'
}
