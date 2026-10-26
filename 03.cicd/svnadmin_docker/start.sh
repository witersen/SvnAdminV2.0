#!/bin/bash

source /etc/profile

# 清理镜像内可能残留的 PID，避免状态检测误判
rm -f /home/svnadmin/svnserve.pid

# PHP-FPM（Apache 通过 mod_proxy_fcgi 转发 .php 请求）
/usr/sbin/php-fpm || { echo 'php-fpm failed to start'; exit 1; }
for i in $(seq 1 30); do
    [ -S /run/php-fpm/www.sock ] && break
    sleep 1
done
[ -S /run/php-fpm/www.sock ] || { echo 'php-fpm socket not ready'; exit 1; }

# SVN
/usr/bin/svnserve --daemon --pid-file=/home/svnadmin/svnserve.pid -r '/home/svnadmin/rep/' --config-file '/home/svnadmin/svnserve.conf' --log-file '/home/svnadmin/logs/svnserve.log' --listen-port 3690 --listen-host 0.0.0.0

# SASL（LDAP 认证）
spid=$(uuidgen)
/usr/sbin/saslauthd -a 'ldap' -O "$spid" -O '/home/svnadmin/sasl/ldap/saslauthd.conf'
ps aux | grep -v grep | grep "$spid" | awk 'NR==1' | awk '{print $2}' > '/home/svnadmin/sasl/saslauthd.pid'
chmod 777 /home/svnadmin/sasl/saslauthd.pid

# 计划任务
/usr/sbin/crond
/usr/sbin/atd

# 后台守护进程
/usr/bin/php /var/www/html/server/svnadmind.php start &

# Apache 作为 PID 1 前台运行
rm -rf /run/httpd
mkdir -p /run/httpd
chown -R apache:apache /run/httpd
exec /usr/sbin/httpd -D FOREGROUND
