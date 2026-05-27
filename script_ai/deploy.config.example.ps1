# Copy to deploy.local.ps1 if needed.

@{
    SshHost      = '24logist.ru'
    SshUser      = 'logist_sys'
    RemoteWebDir = '/var/www/logist_sys/data/www/24logist.ru'
    RemoteAppDir = '/var/www/logist_sys/data/www/24logist.ru/.app'
    GitBranch    = 'main'
}
