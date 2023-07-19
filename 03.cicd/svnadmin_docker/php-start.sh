#!/bin/bash

# 重启 cron
cron restart

# 使 php-fpm 工作在前台
/usr/local/sbin/php-fpm