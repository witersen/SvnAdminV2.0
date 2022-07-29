# SVNAdmin - 开源SVN管理系统
- 该系统为使用PHP开发的基于web的Subversion（SVN）服务器端管理工具，支持docker部署
- 支持功能：SVN仓库管理、SVN用户管理、SVN分组管理、目录授权、目录浏览、Hooks管理、在线dump备份、在线备份恢复、SVN用户禁用、服务器状态管理、日志管理、消息通知、更新检测...
- 演示地址：http://svnadmin.witersen.com (默认的用户名与密码都为 admin)
- 项目地址：
  - GitHub地址：https://github.com/witersen/SvnAdminV2.0 
  - Gitee地址：https://gitee.com/witersen/SvnAdminV2.0
- 发行包：
  - GitHub：https://github.com/witersen/SvnAdminV2.0/releases/download/v2.3.2/v2.3.2.zip
  - Gitee：https://gitee.com/witersen/SvnAdminV2.0/attach_files/1126164/download/v2.3.2.zip
- 兼容性

  - 本程序提供 docker 镜像，基于 centos7.9.2009 构建

  - 操作系统（手动安装）：CentOS7（推荐）、CentOS8、Rocky、Ubuntu（Windows及其它Linux发行版正在测试兼容中）
  - PHP版本：5.5 <= PHP < 8.0
  - 数据库：SQLite、MySQL
  - Subversion：1.8+
- 有问题或建议可留言或加群：  QQ群 633108141
- 感觉有用请留下个 ⭐ 吧

## 一、手动安装

### 1、在CentOS7.6操作系统裸机安装示例

- 安装PHP和相关扩展

```
# 解压缩和网络获取工具
yum install -y zip unzip wget vim

# 由于CentOS7默认源中提供的PHP版本为5.4，因此我们使用remi源安装更高的php版本
yum install -y epel-release
wget http://rpms.remirepo.net/enterprise/remi-release-7.rpm
rpm -Uvh remi-release-7.rpm

# 编辑 /etc/yum.repos.d/remi.repo 文件，将想安装的PHP版本下方的enable由0改为1
vim /etc/yum.repos.d/remi.repo

# 安装php及相关扩展
yum install -y php php-common php-cli php-fpm php-json php-mysqlnd php-process php-json php-gd php-bcmath
```

- 安装web服务器

```
# 以apache为例
yum install -y httpd
systemctl start httpd
systemctl enable httpd
```

- 安装本程序

```
# 将代码包下载到 /var/www/html/ 目录并解压
cd /var/www/html/

# 代码包从发行版获取
wget https://gitee.com/witersen/SvnAdminV2.0/attach_files/1126164/download/v2.3.2.zip

# 解压
unzip v2.3.2.zip
```

- 安装Subversion（如果你安装过Subversion，本步骤可以略过）

```
# 由于CentOS7.6默认源中的Subversion版本为1.7 因此我们需要通过安装脚本安装高版本（>=1.8）

# 切换目录
cd /var/www/html/server/

# install.php文件可以帮助我们安装Subversion
php install.php
```

- 修改Subversion的配置使其支持被本系统管理

```
#切换到目录
cd /var/www/html/server

#install.php文件可以帮助我们配置Subversion
php install.php
```

- 将本程序加入系统管理和开机自启（系统管理）（推荐）（与下方启动方式二选一即可）

```
#新建文件 svnserve.service
vim /usr/lib/systemd/system/svnadmind.service

#写入以下内容
#注意 /var/www/html/server/svnadmind.php 要改为自己实际的文件路径
#文件名称为 svnadmind 则表示我们新建的服务名称为 svnadmind
[Unit]
Description=SVNAdmin
After=syslog.target network.target

[Service]
Type=simple
ExecStart=/usr/bin/php /var/www/html/server/svnadmind.php start

[Install]
WantedBy=multi-user.target

#启动
systemctl daemon-reload
systemctl start svnadmind

#查看状态
systemctl status svnadmind

#加入开机自启动
systemctl enable svnadmind

#取消开机自启动
systemctl diable svnadmind
```

- 启动本程序的后台进程（手动管理）（与上方启动方式二选一即可）

```
#正式启动（后台模式）
nohup php svnadmind.php start >/dev/null 2>&1 &

#停止
php svnandmin.php stop

#调试模式
php svnadmin.php console

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
  - 同上


### 3、在ubutntu18安装示例

- 注意以root用户执行 server/install.php 和 server/svnadmind.php 即可

### 4、在Rocky安装示例

- 无注意事项 同 1.1

## 二、docker安装

- 拉取镜像

  - ```
    #拉取镜像
    docker pull witersencom/svnadmin:2.3.2
    ```

- 仅运行查看效果（不挂载数据）

  - ```
    docker run -d \
    --name svnadmintemp \
    -p 80:80 \
    -p 3690:3690 \
    --privileged \
    witersencom/svnadmin:2.3.2
    ```

- 用于生产环境（挂载数据到容器中，容器销毁数据不会丢失）

  - 新用户

    - ```
      #启动一个临时容器，并将配置文件复制出来
      docker run -d \
      --name svnadmintemp \
      --privileged=true \
      witersencom/svnadmin:2.3.2 \
      /usr/sbin/init
      
      #复制的数据目录为 /home/svnadmin/
      cd /home/
      docker cp svnadmintemp:/home/svnadmin ./
      
      #停止并删除临时容器
      docker stop svnadmintemp && docker rm svnadmintemp
      
      #启动正式容器
      docker run -d -p 80:80 -p 3690:3690 -v /home/svnadmin/:/home/svnadmin/ --privileged witersencom/svnadmin:2.3.2
      ```
  
  - 老用户（2.3.1+）
  
    - ```
      #假设数据存储主目录在宿主机的位置为 /home/svnadmin/ 则直接按照下面方式启动即可 会自动将宿主机数据挂载到容器中
      docker run -d \
      -p 80:80 \
      -p 3690:3690 \
      -v /home/svnadmin/:/home/svnadmin/ \
      --privileged \
      svnadmin:2.3.2
      ```

## 三、手动升级

PS: 如果之前在配置文件 $path/config/database.php 中手动切换了MySQL数据库，升级后还需要重配置数据库信息

###  3.1、docker用户

```
#数据保留在宿主机，停止并删除原来的容器，直接拉取新镜像启动即可
#假设旧版本为 old 新版本为 new

docker stop old && docker rm old

docker run -d -p 80:80 -p 3690:3690 -v /home/svnadmin/:/home/svnadmin/ --privileged new
```

### 3.2、非docker用户

具体操作步骤如下：

```
#假设你的代码部署在 /var/www/html/ 目录下
cd /var/www/html/
```
- 停止守护进程
```
#如果已经加入系统管理 通过 systemctl 关闭
systemctl stop svnadmind

#如果手动安装 通过手动关闭
php server/svnadmind.php stop
```
- 备份

```
cd /var/www/html/
tar -czvf backup.tar.gz .* --remove-files
```
- 部署新版本代码
```
cd /var/www/html/
wget https://gitee.com/witersen/SvnAdminV2.0/attach_files/1126164/download/v2.3.2.zip
unzip v2.3.2.zip
```
- 升级Subversion版本（1.8+）（>=1.8则无需升级）
```
#执行脚本并选择使用第1个选项
php server/install.php
```
- 执行适配程序
```
#执行脚本并选择使用第2个选项，选择不覆盖原来的 autzh 、passwd、svnadmin.db 等文件
php server/install.php
```
- 启动后台程序
```
启动方式见步骤一
```

## 四、功能介绍

- 登录界面可分角色登录，配合后端实现的登录验证码更安全（验证码可后台手动关闭开启）

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

## 五、待办事项

- [ ] 支持常见文件在线浏览
- [ ] 支持文件和文件夹在线下载
- [x] 支持重设仓库UUID
- [ ] 删除仓库需要输入管理人员密码
- [x] 支持修改应用根目录
- [ ] 支持authz、passwd文件的在线识别导入和导出
- [x] docker部署
- [ ] 邮件发送和仓库备份等使用异步任务
- [ ] 支持在线仓库版本过滤、仓库版本互传
- [ ] 在仓库列表以突出颜色标记不被支持管理的仓库，如低版本的仓库
- [ ] 支持webhook
- [ ] 支持配置OSS进行备份
- [ ] 支持修改封面背景图片
- [ ] 仓库目录树视图中，文件或目录被授权过会有红点提示
- [ ] 增加多种备份方式的支持：如 svnadmin hotcopy
- [ ] 安装svnadmin的机器之间可进行远程同步
- [ ] 支持使用svnauthz-validate检查authz配置信息的正确性作为高级功能
- [ ] 支持使用三种认证选项（Apache+mod_dav_svn、svnserve(用户文件和SASL)、svnserve+SSH）
- [ ] 开发微信小程序端开发（针对提交提醒）

## 六、FAQ

### 1、如何将已有的SVN仓库使用此系统管理 ？

- （1）安装本系统
- （2）执行 php server/install.php  使用内置的功能重新配置你的Subversion
- （3）将已有的一个或多个SVN仓库移动到 /home/svnadmin/rep/ 目录下 
- （4）刷新管理系统的仓库管理页面即可识别SVN仓库
- （5）注意此方式并不会识别SVN仓库原有的用户以及权限配置，因为我们使用了统一的配置文件来进行用户和权限管理，因此迁移仓库后还需要在管理系统重新添加用户、用户组、配置权限！

### 2、如何将数据库切换为MySQL ？

- 创建数据库

- 将系统提供的 mysql 数据库文件导入到你的MySQL数据库

- 修改 config/database.php 将sqlite部分注释并配置你的MySQL即可
- 注意：若php版本过低而MySQL版本>=8.0，则会提示：The server requested authentication method unknown to the client，只需要升级php版本或者修改MySQL数据库的配置信息即可

### 3、为什么只支持管理Subversion1.8+ ？

- 预计在 2.5.x 版本向下适配，支持管理 Subversion 1.5+

### 4、为什么目前只支持Linux操作系统 ？

- 正在使用新方案对Windows操作系统进行支持测试
- 预计在 2.4.x 版本支持 Windows 部署

### 5、仓库初始化结构模板 ？

- 我们可以在创建仓库的时候选择创建指定内容结构的仓库，如包含 "trunk" "branches" "tags" 文件夹的结构，这一结构是可选的并且可调整的，我们可以手动调整 /home/svnadmin/templete/initStruct/01/ 下的目录结构

### 6、常用钩子推荐 ？

- 我们可以在目录 /home/svnadmin/hooks/ 下增加自己常用的钩子 
  - /home/svnadmin/hooks/ 下建立文件夹 xx，名称任意
  - 在 xx 下新建文件 hookDescription 写入对此钩子的描述文本内容
  - 在 xx 下新建文件 hookName 写入钩子类型，如post-commit等
  - 在 xx 下新建文件 ，以钩子类型命名，如 post-commit ，然后写入具体钩子内容
- 感谢 【北方糙汉子-】提供的钩子脚本

### 7、关于Subversion 权限配置中的魔力符号

- Subversion从1.5开始支持用户使用一些魔力符号如 $authenticated 、$anonymous
- 预计在 2.3.3 版本支持 Subversion 的全部权限配置特性

### 8、关于与LDAP对接

- 预计在 2.4 版本重新规划系统权限分配，并支持 LDAP 等认证方式

### 9、如何找回密码

- 使用默认的SQLite数据库
```
#使用sqlite数据库

yum install -y sqlite-devel

cd /home/svnadmin

sqlite3 svnadmin.db

.header on

.mode column

select * from admin_users;
```

- 使用MySQL数据库
  - 使用可视化工具登录到数据库查看 admin_users 数据表信息即可

### 10、关于大文件下载中断问题

- 当下载1G以及以上的大文件会出现下载被中断的问题，是因为文件下载为了安全没有使用http文件直链，而是通过php校验后读取文件流下载，所以会存在一个php-fpm最大执行时间的问题，因此你可以通过 设置 php-fpm.conf 配置文件的 request_terminate_timeout 为0 来取消超时限制

### 11、本软件的工作模式

- 通过使 svnadmind.php 成为守护进程并监听指定端口来工作
- php-fpm与php-cli程序的使用TCP套接字通信

  ![](./00.static/03.daemon/work.png)