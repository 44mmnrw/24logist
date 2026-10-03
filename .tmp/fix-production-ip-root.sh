#!/usr/bin/env bash
set -euo pipefail

if [[ "$(id -u)" != 0 ]]; then
    echo 'Run this script as root on 5.42.114.199.' >&2
    exit 1
fi

hosts_file=/etc/hosts
proxy_file=/etc/apache2/mods-available/rpaf.conf
old_ip=147.45.236.73

if ! grep -qF "$old_ip" "$hosts_file" "$proxy_file"; then
    echo 'The old IP is already absent from both files.'
    exit 0
fi

apache2ctl configtest
backup_dir=$(mktemp -d /root/logistru-ip-fix.XXXXXXXX)
cp -p -- "$hosts_file" "$backup_dir/hosts"
cp -p -- "$proxy_file" "$backup_dir/rpaf.conf"

rollback() {
    cp -p -- "$backup_dir/hosts" "$hosts_file"
    cp -p -- "$backup_dir/rpaf.conf" "$proxy_file"
    echo "Restored original files. Backups: $backup_dir" >&2
}
trap rollback ERR

sed -i 's/147\.45\.236\.73/5.42.114.199/g' "$hosts_file" "$proxy_file"
apache2ctl configtest
systemctl reload apache2
trap - ERR

echo "Updated /etc/hosts and Apache proxy IP. Backups: $backup_dir"
