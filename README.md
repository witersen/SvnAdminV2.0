# SVNAdmin2 - 基于web的SVN管理系统

### 1. 介绍

- SVNAdmin2 是一款**通过图形界面管理服务端SVN的web程序**。

- 正常情况下配置SVN仓库的人员权限需要登录到服务器手动修改 authz 和 passwd 两个文件，当仓库结构和人员权限上了规模后，手动管理就变的非常容易出错，本系统能够识别人员和权限并提供管理和拓展功能。

- SVNAdmin2 支持**SVN协议检出、HTTP协议检出**，并且支持两种协议之间互相切换，支持docker部署或源码部署。

- SVNAdmin2 支持进行**LDAP的接入**，进而达到使用原有的人员架构和分组规则的目的。

- [GitHub地址](https://github.com/witersen/SvnAdminV2.0)   [Gitee地址](https://gitee.com/witersen/SvnAdminV2.0)

- 问题求助、功能建议、更新计划、SVN技术讨论，可加QQ群：**633108141**

- 项目演示地址：http://svnadmin.witersen.com (管理人员/admin/admin)

- 系统截图

<img src="00.static/demo.jpg" alt="" width="100%" height="100%" />



### 2. 兼容性

**docker > Rocky Linux 9 > Rocky Linux 8 > CentOS 8 > Ubuntu**

Windows下如有需求，可使用 docker 版本

PHP版本：**>= 8.3.36**（推荐 8.3.x；8.3.36 起修复 CVE-2026-109618 / CVE-2026-109619）

数据库：SQLite、MySQL

Subversion：1.8+（Docker 镜像内置 1.14）

> **安全提示（2.5.11）**：旧镜像使用的 PHP 7.4 已 EOL，且 REMI EL7 的 php83 仓库停在 8.3.8，不包含 PHP2Shell 修复。2.5.11 起基础镜像迁移到 Rocky Linux 9 + PHP 8.3.36；升级时必须拉取新镜像并重建容器，不能只 restart 旧容器。源码部署也必须先安装 PHP >= 8.3.36。



### 3. docker安装

[nas用户的docker部署教程在这里](https://www.bilibili.com/video/BV1QM4y147b3/)

##### 3.1 适用于：快速部署看效果

*ps:镜像默认托管在docerhub，如果速度不佳，可以选择国内路线（docker pull registry.cn-hangzhou.aliyuncs.com/witersencom/svnadmin:[镜像版本号]）*

> 本仓库 CI 产出的完整 tag 形如 `2.5.11-php83-svn1.14`。下面示例中的 `witersencom/svnadmin:2.5.11` 请替换为你实际发布的镜像地址；如果没有 `2.5.11` 别名，请直接使用完整 tag。
>
> GitHub Actions 会把镜像推送到 GHCR：`ghcr.io/<仓库所有者>/svnadmin`，包含 `2.5.11`、`2.5.11-php83-svn1.14`、`sha-<短提交>`，默认分支额外推送 `latest`。PR 只构建并执行冒烟测试，不推送镜像。

此方式可快速部署程序体验效果，数据不存储在宿主机，生产环境使用请看3.2

```
docker run -d --name svnadmintemp -p 80:80 -p 3690:3690 --privileged witersencom/svnadmin:2.5.11
```

##### 3.2 适用于：新用户正式使用

- 创建一个临时的容器（无需启动）用于复制配置文件出来

```
docker create --name svnadmintemp witersencom/svnadmin:2.5.11
```

- 把配置文件复制到本机的 `/home/svnadmin` 目录

```
cd /home/
docker cp svnadmintemp:/home/svnadmin ./
docker cp svnadmintemp:/etc/httpd/conf.d ./svnadmin/
docker cp svnadmintemp:/etc/sasl2 ./svnadmin/
```

- 删除掉临时容器

```
docker rm svnadmintemp
```

- 启动正式的容器

```
docker run -d -p 80:80 -p 3690:3690 \
-v /home/svnadmin/:/home/svnadmin/ \
-v /home/svnadmin/conf.d/:/etc/httpd/conf.d/ \
-v /home/svnadmin/sasl2/:/etc/sasl2/ \
--privileged \
--name svnadmin \
witersencom/svnadmin:2.5.11
```

- 进入容器内进行文件授权

```
docker exec -it svnadmin bash
chown -R apache:apache /home/svnadmin
```

##### 3.3 适用于：旧用户升级

*ps: 2.4.3 及之前的用户要注意目录挂载多了 conf.d sasl2 升级之前要提前复制出来*

- 2.5.10 及更早版本升级到 2.5.11
  - 本次升级包含 **PHP 运行时升级（>= 8.3.36）**，必须使用 2.5.11 的新镜像或完整源码包，不能只在旧容器内执行 update
  - 升级前备份 `/home/svnadmin`、`/etc/httpd/conf.d`、`/etc/sasl2`
  - 停止旧容器，拉取 2.5.11 镜像，挂载原数据目录启动；启动后进入容器确认 `php -v` >= 8.3.36
  - 源码部署：先在 Rocky/RHEL 9（或 Ubuntu）安装 PHP >= 8.3.36 与 php-fpm，替换代码后执行 `php server/install.php`，再重启 `svnadmind` 与 Apache/php-fpm
  - 升级后可执行只读冒烟测试：`bash 03.cicd/smoke_test.sh <容器名> http://127.0.0.1:<端口> admin admin`

### 4. 源码安装

svnadmin = web系统 + 后台进程，因此安装注意噢

##### 4.1 适用于：Rocky Linux 9 / RHEL 9

- 安装解压缩等工具

```
dnf install -y zip unzip wget vim which
```

- 安装sasl相关依赖（svn协议检出配置sasl认证如ldap要用到）

```
dnf install -y cyrus-sasl cyrus-sasl-lib cyrus-sasl-plain
```

- 安装PHP和相关扩展（要求 PHP >= 8.3.36，使用 remi 的 php:remi-8.3 模块）

```
dnf install -y epel-release dnf-plugins-core
rpm -Uvh https://rpms.remirepo.net/enterprise/remi-release-9.rpm
dnf module reset php || true
dnf module enable php:remi-8.3

dnf install -y php-cli php-common php-fpm php-mysqlnd php-pdo php-process php-gd php-bcmath php-ldap php-mbstring php-opcache
```

- 安装web服务器（推荐 apache 可使用http协议检出；php-fpm 包会自动写入 Apache 的 php.conf）

```
dnf install -y httpd mod_dav_svn mod_ldap
systemctl start httpd
systemctl enable httpd
systemctl start php-fpm
systemctl enable php-fpm
```

- 安装任务计划组件（任务计划功能用到）

```
dnf install -y cronie at

#启动atd（如果 ps aux | grep -v 'grep' | grep atd 的结果为空需要执行）
atd

#启动crond（如果 ps aux | grep -v 'grep' | grep crond 的结果为空需要执行）
crond
```

- 下载解压代码包

```
# 注意：请将下面的地址替换为你自己仓库的 2.5.11 发布包地址
cd /var/www/html/ && wget https://gitee.com/witersen/SvnAdminV2.0/releases/download/2.5.11/2.5.11.zip

unzip 2.5.11.zip
```

- 安装Subversion（如果你安装过Subversion，本步骤可以略过）（注意需要Subversion >= 1.8）

```
cd /var/www/html/server/
chown -R apache:apache /var/www/html/
#选项1
php install.php
```

- 修改Subversion的配置使其支持被本系统管理（如果你安装过Subversion，本步骤要执行）

```
cd /var/www/html/server
#选项1或选项2
php install.php
```

- 为数据目录授权属主和属组。php脚本web调用是以apache身份执行，因此apache用户需要对数据目录有权
- 如果你使用其它web服务器如nginx tomcat 可以通过浏览器访问 你的机器IP/server/own.php 来获取属主和属组

```
chown -R apache:apache /home/svnadmin
```

- 手动启动后台进程（启动方式一）

  ```
  #pwd
  #/var/www/html/server/
  
  #后台运行
  nohup php svnadmind.php start >/dev/null 2>&1 &
  #后台运行后输入exit退出一下 保证后台稳定运行
  exit
  
  #停止后台
  php svnadmind.php stop
  
  #调试模式
  php svnadmind.php console
  ```

- 通过系统管理启动后台进程（启动方式二）

  - 新建系统服务文件 svnserve.service（centos一般为 /usr/lib/systemd/system/svnadmind.service、ubuntu 一般为 /lib/systemd/system/svnadmind.service）
    - 写入以下内容（注意根据自己的代码部署路径调整）

  ```
  [Unit]
  Description=SVNAdmin
  After=syslog.target network.target
  
  [Service]
  Type=simple
  ExecStart=/usr/bin/php /var/www/html/server/svnadmind.php start
  
  [Install]
  WantedBy=multi-user.target
  ```

    - 操作服务

  ```
  #启动
  systemctl daemon-reload
  systemctl start svnadmind
  
  #查看状态
  systemctl status svnadmind
  
  #加入开机自启动
  systemctl enable svnadmind
  ```

##### 4.2 适用于：宝塔面板

- 安装方式跟手动部署类似，只是宝塔系统了很多可视化操作很方便

- 参考视频：[SVNAdmin V2.2.1 系统部署与使用演示视频【针对宝塔面板】]( https://www.bilibili.com/video/BV1XR4y1H7p3?share_source=copy_web&vd_source=f4620db503611c42618f1afd9c8afecd) 

##### 4.3 适用于：Ubuntu 22.04 / 24.04

- 步骤同1（注意需要以root用户执行 server/install.php 和 server/svnadmind.php ）
- 在ubuntu中软件包名称多与RHEL系列不同，需要用户自行处理
- 要求 PHP >= 8.3.36，下面使用 ondrej/php PPA

```
sudo apt-get update

sudo apt-get install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt-get update

sudo apt-get install -y apache2
sudo apt-get install -y php8.3-cli php8.3-fpm php8.3-mysql php8.3-sqlite3 php8.3-gd php8.3-ldap php8.3-mbstring php8.3-bcmath php8.3-opcache

sudo a2enmod proxy_fcgi setenvif
sudo a2enconf php8.3-fpm
sudo systemctl restart apache2
sudo systemctl enable php8.3-fpm
sudo systemctl start php8.3-fpm

sudo apt-get install -y sqlite3

sudo apt-get install -y subversion subversion-tools

cd /var/www/html

wget xxx.zip

unzip xxx.zip

#选项2
sudo server/install.php

chown -R apache:apache /home/svnadmin/

su root

nohup php server/svnadmind.php start &
```

##### 4.4 适用于：旧用户升级

- 2.3.x和2.4.x和2.5.x升级到2.5.11
  - 先按 4.1 把 PHP 升级到 **>= 8.3.36**（EL7 的 remi-php83 停在 8.3.8，必须迁移到 Rocky/RHEL 9 或 Ubuntu）
  - dnf install -y unzip
  - 替换代码后执行 `cd /var/www/html/server && php install.php`
  - 重启 `svnadmind` 与 Apache/php-fpm

### 5. 常见问题解答

##### 5.1 使用此系统管理管理之前的仓库 ？

- 确认之前SVN仓库的版本，如果是1.8+则无需担心，如果是1.8以下，则需要简单升级下仓库

- 安装本系统
- 执行 php server/install.php  使用内置的功能重新配置你的Subversion
- 将已有的一个或多个SVN仓库移动到 /home/svnadmin/rep/ 目录下 
- 在导航**SVN仓库**中执行**同步列表**，即可识别SVN仓库
- 注意：如果你原来是一个仓库一套配置文件的方式，则还需要按照截图的方式稍微调整下你的配置文件。因为现在是多个仓库一套配置文件的管理方式。

<img src="00.static/qianyi.png" alt="" width="45%" height="45%" />

##### 5.2 如何将数据库切换为MySQL ？

- 创建数据库 svnadmin
- 将安装包中的MySQL文件 templete/database/mysql/svnadmind.sql 导入数据库
- 修改 config/database.php 将sqlite部分注释并配置你的MySQL即可
- 注意：2.5.11 要求 PHP >= 8.3.36，已原生支持 MySQL 8 的认证方式；如仍遇到 `The server requested authentication method unknown to the client`，请确认 PHP 版本并检查 MySQL 用户认证插件

##### 5.3 为什么只支持管理Subversion1.8+ ？

- 因为目前是通过多个仓库读取一套配置文件的方式，而subversion1.8+才支持这种方式
- 预计在 2.5.x 版本向下适配，支持管理 Subversion 1.5+

##### 5.4 为什么目前只支持Linux操作系统 ？

- 系统中使用了一些多进程的方案，而这在Windows下实现需要花费更多的时间

- 短期内没有支持Windows部署的计划
- Windows下使用可通过docker版本

##### 5.5 仓库初始化结构模板 ？

- 我们可以在创建仓库的时候选择创建指定内容结构的仓库，如包含 "trunk" "branches" "tags" 文件夹的结构，这一结构是可选的并且可调整的，我们可以手动调整 /home/svnadmin/templete/initStruct/01/ 下的目录结构

##### 5.6 常用钩子推荐 ？

- 我们可以在目录 /home/svnadmin/hooks/ 下增加自己常用的钩子 
  - /home/svnadmin/hooks/ 下建立文件夹 xx，名称任意
  - 在 xx 下新建文件 hookDescription 写入对此钩子的描述文本内容
  - 在 xx 下新建文件 hookName 写入钩子类型，如post-commit等
  - 在 xx 下新建文件 ，以钩子类型命名，如 post-commit ，然后写入具体钩子内容

##### 5.7 管理员找回密码

- 使用默认的SQLite数据库
```
#使用sqlite数据库

dnf install -y sqlite

cd /home/svnadmin

sqlite3 svnadmin.db

.header on

.mode column

select * from admin_users;
```

- 使用MySQL数据库
  - 使用可视化工具登录到数据库查看 admin_users 数据表信息即可

##### 5.8 关于大文件下载中断问题

- 当下载1G以及以上的大文件会出现下载被中断的问题，是因为文件下载为了安全没有使用http文件直链，而是通过php校验后读取文件流下载，所以会存在一个php-fpm最大执行时间的问题，因此你可以通过 设置 php-fpm.conf 配置文件的 request_terminate_timeout 为0 来取消超时限制

##### 5.9 如果配置了多个仓库模板，如何在创建仓库时指定使用某个仓库模板？

```
例如： 
在 /home/svnadmin/templete/initStruct/01 下面配置第一个仓库结构模板
在 /home/svnadmin/templete/initStruct/02 下面配置第二个仓库结构模板
如果在web中创建时，如何选用默认的 /home/svnadmin/templete/initStruct/02 下面的仓库结构模板？
【解决方案】
由于时间问题，开发时并没有对此功能做更多的详细开发，因此只预留了配置文件层面的修改途径，后续会将仓库模板功能加入到web配置，无需手动命令行管理
可以通过修改 config/svn.php 中的 templete_init_struct_01 值来修改
```

##### 5.10 配置了自定义仓库模板但是创建仓库时没有生效

```
注意配置自定义仓库模板的位置 
通常的位置在 /home/svnadmin/templete/initStruct/01 下面 
而不是在项目代码相关的位置
```

##### 5.11 数据长度超过8192 请向上调整参数：SOCKET_READ_LENGTH

```
【出现问题原因】
svn的用户量和权限配置数量增加，超过了默认值
【解决方案】
修改 config/daemon.php 文件中的参数
```

##### 5.12 提示无法连接到LDAP服务器

```
确定以下两点
1、你的 ldap 服务器地址和端口真实有效
2、安装 svnadmin2 的机器确实可以通过 ldap 端口与你的 ldap 服务所在服务器通信

然后检查 svnadmin2 所在机器的 selinux 配置，通常 selinux 会禁止 http 连接 ldap
执行：
getsebool -a | grep ldap
如果得到以下结果：
httpd_can_connect_ldap --> off
证明需要我们手动开启 httpd_can_connect_ldap 这个选项
执行以下指令允许连接即可
setsebool -P httpd_can_connect_ldap=1

以上情况为 selinux 开启可能出现的，当关闭 selinux 后以上配置不再起作用
```

##### 5.13 LDAP启用状态下用户已授权但是无权限浏览仓库

```
这种情况通常是因为源码安装过程中，少装了有关ldap 的模块或依赖，建议详细看文档
```

##### 5.14 LDAP状态下用户列表同步成功但是无法登录（2.4.x版本之前的问题）

```
这种情况下通常是因为你的 Base DN 配置问题
假设
你的 base dn 填写为：dc=witersen,dc=com
你的 Attributes 填写为： cn
然后你过滤出用户：blue
那么 blue 作为一个 SVN 用户来登录系统的时候，系统会将 cn=blue,dc=witersen,dc=com 来作为用户 blue 的完整 dn 并且结合用户输入的密来一起请求 ldap 服务器进行校验，所以如果 blue 用户的真实 dn 是 cn=blue,ou=devGroup,dc=witersen,dc=com 就会造成同步成功登录失败的情况
```

##### 5.15 LDAP可以获取到用户、分组，但是无法把用户同步为分组的成员

```
参考文章：https://www.witersen.com/?p=1844
其中关于如何对接LDAP作了详细解释
```