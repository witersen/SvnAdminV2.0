# SVNAdmin 系统部署与使用手册
1、该系统为使用PHP开发的基于web的Subversion（SVN）服务器端管理工具

2、支持操作系统：CentOS7（推荐）、CentOS8、Rocky、Ubuntu（其它Linux发行版正在测试兼容中）

3、支持PHP版本：5.5 <= PHP < 8.0

4、支持数据库：SQLite、MySQL

5、支持Subversion：1.8+

6、支持功能：SVN仓库管理、SVN用户管理、SVN分组管理、目录授权、目录浏览、Hooks管理、在线dump备份、在线备份恢复、SVN用户禁用、服务器状态管理、日志管理、消息通知、更新检测...

7、演示地址：http://svnadmin.witersen.com (默认的用户名与密码都为 admin)

8、GitHub地址：https://github.com/witersen/SvnAdminV2.0 Gitee地址：https://gitee.com/witersen/SvnAdminV2.0

9、可以加入群聊讨论遇到的问题  QQ群 633108141

## 一、安装示例

### 1、在CentOS7.6操作系统裸机安装示例

- 安装PHP和相关扩展

```
#解压缩和网络获取工具
yum install -y zip unzip wget

#由于CentOS7默认源中提供的PHP版本为5.4，因此我们使用remi源安装不同php版本
yum install -y epel-release
wget http://rpms.remirepo.net/enterprise/remi-release-7.rpm
rpm -Uvh remi-release-7.rpm

#编辑 /etc/yum.repos.d/remi.repo 文件，将想安装的PHP版本下方的enable由0改为1
vim /etc/yum.repos.d/remi.repo

#开始安装php及相关扩展
yum install -y php
yum install -y php-common
yum install -y php-cli
yum install -y php-fpm
yum install -y php-json
yum install -y php-mysqlnd
yum install -y php-process
yum install -y php-json
yum install -y php-gd
```

- 安装web服务器

```
#以apache为例
yum install -y httpd
systemctl start httpd
systemctl enable httpd
```

- 安装本程序

```
#将代码包下载到 /var/www/html/ 目录并解压
cd /var/www/html/

#代码包从发行版获取
wget xxx.zip

#解压
unzip xxx.zip
```

- 安装Subversion（如果你安装过Subversion，本步骤可以略过）

```
#由于CentOS7.6默认源中的Subversion版本为1.7 因此我们需要通过安装脚本安装高版本（>=1.8）

#切换目录
cd /var/www/html/server/

#install.php文件可以帮助我们安装Subversion
php install.php
```

- 修改Subversion的配置使其支持被本系统管理

```
#切换到目录
cd /var/www/html/server

#install.php文件可以帮助我们配置Subversion
php install.php
```

- 启动本程序的后台进程

```
#以守护进程模式启动（通常情况下是这样）
php svnadmind.php start

#以调试模式启动（出问题可自行调试）
php svnadmin.php console

#停止（有问题或想停止服务，可自行停止）
php svandmin.php stop
```

- 将本程序加入开机自启（可选）

```
todo ..........
```

### 2、在安装宝塔面板的操作系统安装示例

- 创建站点
- 将PHP的命令行版本更换为要求的php版本
- 关闭站点的 open_basedir 防跨站攻击选项
- 将站点的PHP版本更换为要求的PHP版本
- 在软件商店对应版本的php中删除禁用的函数 
  - pcntl_signal
  - pcntl_fork
  - pcntl_wait
  - shell_exec
  - passthru
- 安装和启动
  - 进入server目录
  - php install.php 进行安装和配置Subversion
  - php svnadmind.php start 启动后台程序

### 3、在ubutntu18安装示例

- 注意以root用户执行 server/install.php 和 server/install.php 即可

### 4、在Rocky安装示例

- 无注意事项 同1

## 二、docker安装

- 暂无时间制作docker包，等待大家贡献或者自己有时间去做

## 三 、功能介绍

- 登录界面可分角色登录，配合后端实现的登录验证码更安全

  ![](./00.static/01.demo/01.jpg)
  
- 服务器状态监控和信息统计，对当前服务器状态和SVN仓库信息更加了解

  ![](./00.static/01.demo/02.jpg)
  
- SVN仓库概览，提供了多种高级功能，还可根据仓库名、版本数、体积等一键排序

  ![](./00.static/01.demo/03.jpg)
  
- 新建SVN仓库提供两种模式 随意选择

  ![](./00.static/01.demo/04.jpg)
  
- 在线目录浏览更加方便 逐层加载，服务资源占用更低

  ![](./00.static/01.demo/05.jpg)
  
- 通过目录面包屑可以随时回到某级目录 可以看到目录的作者、版本、提交日期、提交日志等，还可一键复制检出地址

  ![](./00.static/01.demo/06.jpg)
  
- 仓库授权精确到文件级别，可对用户和用户组快速授权和更改权限

  ![](./00.static/01.demo/07.jpg)
  
- 支持在线dump方式备份，备份文件可随时下载或删除

  ![](./00.static/01.demo/08.jpg)
  
- 支持对SVN仓库钩子的管理

  ![](./00.static/01.demo/09.jpg)
  
- 提供了常用钩子，也可以将自己的常用钩子放在这里 

  ![](./00.static/01.demo/10.jpg)
  
- 可以查看每个仓库的详细信息，一键复制详细信息更加方便

  ![](./00.static/01.demo/11.jpg)
  
- 可以将通过dump方式备份的文件再导入仓库 实现SVN仓库的迁移

  ![](./00.static/01.demo/12.jpg)
  
-  仓库导入过程中如果抛出了错误信息会被收集被显示 方便管理人员更好的定位问题 

  ![](./00.static/01.demo/13.jpg)
  
- 可以很方便的修改仓库名称，这会自动同步配置文件，所以无需担心

  ![](./00.static/01.demo/14.jpg)
  
- 删除仓库也会有风险提示  

  ![](./00.static/01.demo/15.jpg)
  
- SVN用户管理支持启用、禁用、添加备注信息，管理用户更加方便

  ![](./00.static/01.demo/16.jpg)
  
- SVN分组支持显示其包含的用户和分组数量 同时支持修改备注信息 

  ![](./00.static/01.demo/17.jpg)
  
- 可以对分组进行用户成员编辑，系统会贴心的提示用户是否处于禁用状态 

  ![](./00.static/01.demo/18.jpg)
  
- 用户管理支持组嵌套，同时如果你不小心搞了一个循环嵌套，系统会提示你 

  ![](./00.static/01.demo/19.jpg)
  
- 分组名支持修改，系统会帮你修改配置文件的一切，无需担心 

  ![](./00.static/01.demo/20.jpg)
  
- 系统提供了日志功能，可以对系统的运转情况做个记录啦 

  ![](./00.static/01.demo/21.jpg)
  
- 管理人员可以修改自己的账号和密码，更加安全 

  ![](./00.static/01.demo/22.jpg)
  
- 系统提供了svnserve主机和端口绑定功能 而且开启了svnserve的运行日志 为你多一层运维保障 

  ![](./00.static/01.demo/23.jpg)
  
- 看看你的数据都在哪里存储呢

  ![](./00.static/01.demo/24.jpg)
  
- 配置邮件通知服务吧

  ![](./00.static/01.demo/25.jpg)
  
- 风险操作可以来个提示

  ![](./00.static/01.demo/26.jpg)
  
- 看看有没有新版本吧

  ![](./00.static/01.demo/27.jpg)
  
- SVN用户个人的界面 只可看到被授权的仓库路径

  ![](./00.static/01.demo/28.jpg)
  
- SVN用户可自己修改密码 无需联系管理人员了

  ![](./00.static/01.demo/29.jpg)

## 四、常见问题解答

### 1、如何将已有的SVN仓库使用此系统管理 ？

- （1）安装本系统
- （2）执行 php server/install.php  使用内置的功能重新配置你的Subversion
- （3）将已有的一个或多个SVN仓库移动到 /home/svnadmin/rep/ 目录下 
- （4）刷新管理系统的仓库管理页面即可识别SVN仓库
- （5）注意此方式并不会识别SVN仓库原有的用户以及权限配置，因为我们使用了统一的配置文件来进行用户和权限管理，因此迁移仓库后还需要在管理系统重新添加用户、用户组、配置权限！

### 2、如何将数据库切换为MySQL ？

- 你只需要修改 config/database.php 将sqlite部分注释并配置你的MySQL即可

### 3、为什么只支持管理Subversion1.8+ ？

- 由于Subversion1.8 之前不支持将多个仓库配置为使用相同的权限配置文件
- 而我们一开始基于Subversion1.10进行开发，因此没有及时的对Subversion1.7等版本进行适配

### 4、为什么只支持Linux操作系统中的部分操作系统 ？

- 本系统的本质为Subversion的旁路性质的辅助管理软件，通过执行一系列的subversion命令行程序来代替手动配置，理论上本系统支持可以安装Subversion和PHP的任何操作系统。
- 但是由于PHP需要高级权限如以root等身份执行一些指令，如svnadmin create ，所以我们在后台运行了一个php守护进程来等待接收和处理执行并返回结果，这个过程使用了pcntl扩展，而这在Windows是不支持的。
- 等我们找到了更好的程序架构方案，可能会解决在Windows的适配问题，但是这个并不影响对众多Linux发行的支持，对Linux发行版的支持需要更多的时间进行适配和改进！

### 5、仓库初始化结构模板 ？

- 我们可以在创建仓库的时候选择创建指定内容结构的仓库，如包含 "trunk" "branches" "tags" 文件夹的结构，这一结构是可选的并且可调整的，我们可以手动调整 /home/svnadmin/templete/initStruct/01/ 下的目录结构

### 6、常用钩子推荐 ？

- 可以。。。。
- 感谢。。。。提供的钩子脚本

### 7、关于Subversion 权限配置中的魔力符号

- Subversion从1.5开始支持用户使用一些魔力符号如 $authenticated 、$anonymous
- 我们使用正则表达式对authz和passwd文件进行匹配和修改
- 由于时间原因，暂时不支持用户的authz文件中使用 Subversion 支持的魔力符号

### 8、关于与LDAP对接

- 与LDAP的对接将会等待一段时间，因为还需要时间使当前版本更稳定

### 9、本软件的工作模式

- 通过使 svnadmind.php 成为守护进程并监听指定端口来工作
- php-fpm与php-cli程序的使用TCP套接字通信

  ![](./00.static/03.daemon/work.png)