# SVNAdmin - 开源SVN管理系统
- 基于web的Subversion（SVN）服务器端管理工具，支持docker部署
- 支持功能：SVN仓库管理、SVN用户管理、SVN分组管理、目录授权、目录浏览、Hooks管理、在线dump备份、在线备份恢复、SVN用户禁用、服务器状态管理、日志管理、消息通知、更新检测...
- 演示地址：http://svnadmin.witersen.com (默认的用户名与密码都为 admin)
- 项目地址：
  - GitHub地址：https://github.com/witersen/SvnAdminV2.0 
  - Gitee地址：https://gitee.com/witersen/SvnAdminV2.0
- 发行包：
  - GitHub：https://github.com/witersen/SvnAdminV2.0/releases/download/v2.3.3.1/2.3.3.1.zip
  - Gitee：https://gitee.com/witersen/SvnAdminV2.0/releases/download/v2.3.3.1/2.3.3.1.zip
- 兼容性

  - 本程序提供 docker 镜像，基于 centos7.9.2009 构建

  - 操作系统（手动安装）：CentOS7（推荐）、CentOS8、Rocky、Ubuntu（Windows及其它Linux发行版正在测试兼容中）
  - PHP版本：5.5 <= PHP < 8.0
  - 数据库：SQLite、MySQL
  - Subversion：1.8+
- 问题协助或功能建议加Q群：633108141

## 一、手动安装

### 1、在CentOS7.6操作系统裸机安装示例

- 安装PHP和相关扩展

```
# 解压缩和网络获取工具
yum install -y zip unzip wget vim

# 由于CentOS7默认源中提供的PHP版本为5.4，而我们需要 5.5+，因此使用remi源
# 可将 remi-php55 切换为想安装的版本 如喜欢 php7.4 则 remi-php74
yum install -y epel-release yum-utils
rpm -Uvh https://mirrors.aliyun.com/remi/enterprise/remi-release-7.rpm
yum-config-manager --enable remi-php74

# 安装php及相关扩展
yum install -y php php-common php-cli php-fpm php-mysqlnd php-mysql php-pdo php-process php-json php-gd php-bcmath
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
wget https://gitee.com/witersen/SvnAdminV2.0/releases/download/v2.3.3.1/2.3.3.1.zip

# 解压
unzip v2.3.3.1.zip
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

  - ```
    #新建文件 svnserve.service
    #centos一般为 /usr/lib/systemd/system/svnserve.service
    #ubuntu 一般为 /lib/systemd/system/svnserve.service
  vim /usr/lib/systemd/system/svnadmind.service
    ```
    
  - ```
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
    ```

  - ```
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

  - ```
    #正式启动（后台模式）
    nohup php svnadmind.php start >/dev/null 2>&1 &
    
    #停止
    php svnandmin.php stop
    
    #调试模式
    php svnadmin.php console
    ```

### 2、在安装宝塔面板的操作系统安装示例

- 安装方式跟手动部署类似，只是宝塔系统了很多可视化操作很方便

- 参考视频：[SVNAdmin V2.2.1 系统部署与使用演示视频【针对宝塔面板】]( https://www.bilibili.com/video/BV1XR4y1H7p3?share_source=copy_web&vd_source=f4620db503611c42618f1afd9c8afecd) 

### 3、在ubutntu18安装示例

- 步骤同1（注意需要以root用户执行 server/install.php 和 server/svnadmind.php ）

- 步骤简要

  - ```
    sudo apt-get install -y apache2
    sudo apt-get install -y php
    sudo apt-get install -y php-cli
    sudo apt-get install -y php-fpm
    
    sudo a2enmod proxy_fcgi setenvif
    sudo systemctl restart apache2
    sudo a2enconf php7.2-fpm
    
    sudo apt-get install -y php-json
    
    sudo apt-get install -y php7.2-mysql
    sudo apt-get install -y php-mysql
    
    sudo apt-get install -y sqlite3
    
    sudo apt-get install -y php7.2-sqlite
    
    sudo apt-get install -y php-gd
    
    sudo systemctl restart apache2
    
    
    sudo apt-get install -y subversion subversion-tools
    
    sudo server/install.php （选项2）
    
    sudo server/svnadmind.php console（调试模式，没问题后按照文档切换为正式模式）
    ```

### 4、在Rocky安装示例

- 步骤同1

## 二、docker安装

- 拉取镜像

  - ```
    #拉取镜像
    docker pull witersencom/svnadmin:2.3.3.1
    ```

- 仅运行查看效果（不挂载数据）

  - ```
    docker run -d \
    --name svnadmintemp \
    -p 80:80 \
    -p 3690:3690 \
    --privileged \
    witersencom/svnadmin:2.3.3.1
    ```

- 用于生产环境（挂载数据到容器中，容器销毁数据不会丢失）

  - 新用户

    - ```
      #启动一个临时容器，并将配置文件复制出来
      docker run -d \
      --name svnadmintemp \
      --privileged=true \
      witersencom/svnadmin:2.3.3.1 \
      /usr/sbin/init
      
      #复制的数据目录为 /home/svnadmin/
      cd /home/
      docker cp svnadmintemp:/home/svnadmin ./
      
      #停止并删除临时容器
      dockeer stop svnadmintemp && docker rm svnadmintemp
      
      #启动正式容器
      docker run -d \
      -p 80:80 \
      -p 3690:3690 \
      -v /home/svnadmin/:/home/svnadmin/ \
      --privileged \
      witersencom/svnadmin:2.3.3.1
      ```
  
  - 老用户（2.3.1+）
  
    - ```
      #假设数据存储主目录在宿主机的位置为 /home/svnadmin/ 则直接按照下面方式启动即可 会自动将宿主机数据挂载到容器中
      docker run -d \
      -p 80:80 \
      -p 3690:3690 \
      -v /home/svnadmin/:/home/svnadmin/ \
      --privileged \
      svnadmin:2.3.3.1
      ```

## 三、手动升级

###  3.1、docker用户

- docker版本只需要停止原来的镜像然后拉取新镜像即可
- 注意将数据存在宿主机

### 3.2、非docker用户

- 程序升级本质就是用新代码替换旧代码，然后用户的数据存储目录无需改变，流程如下：
  - 停止后台 php server/svnadmind.php stop
  - 下载新版本代码，替换旧版本代码
  - 执行适配程序 php server/install.php
    - 执行脚本并选择使用第2个选项，选择不覆盖原来的 autzh 、passwd、svnadmin.db 等文件
  - 重新启动后台
- 如果用户之前自己修改了配置文件，则需要升级后重新修改配置文件

## 四、FAQ

### 1、如何将已有的SVN仓库使用此系统管理 ？

- （1）安装本系统
- （2）执行 php server/install.php  使用内置的功能重新配置你的Subversion
- （3）将已有的一个或多个SVN仓库移动到 /home/svnadmin/rep/ 目录下 
- （4）刷新管理系统的仓库管理页面即可识别SVN仓库
- （5）注意此方式并不会识别SVN仓库原有的用户以及权限配置，因为我们使用了统一的配置文件来进行用户和权限管理，因此迁移仓库后还需要在管理系统重新添加用户、用户组、配置权限！

![](./00.static/01.demo/qianyi.png)

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
- 预计在 2.3.4 版本支持 Subversion 的全部权限配置特性

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

### 11、容器重启后无法正常访问web服务（svn不受影响）

```
【原因】 
重启容器后，容器内的 httpd 由于一些原因没有成功重启 
1、构建的 docker 镜像是以 CentOS7.9.2009 为基础进行的 
由于 CentOS7.9.2009 基础镜像的权限问题：https://github.com/docker-library/docs/tree/master/centos#dockerfile-for-systemd-base-image 
导致如果启动容器时不增加 --privileged 参数 和不以 /usr/sbin/init 作为首先执行的指令，将会导致容器内一些程序无法正常启动 
2、另外不排除重启后再次启动 httpd 时由于上次的 httpd.pid 文件依然造成的识别未 httpd 运行中的误判 
【解决方案】 
如果重启容器后 web 管理系统无法访问 
只需要进入容器并执行下面的命令重新启动 httpd 服务即可 
/usr/sbin/httpd 
或者 
/usr/sbin/httpd -DFOREGROUND & 
后面会考虑更换更方便的解决方案
```

### 12、如果配置了多个仓库模板，如何在创建仓库时指定使用某个仓库模板？

```
例如： 
在 /home/svnadmin/templete/initStruct/01 下面配置第一个仓库结构模板
在 /home/svnadmin/templete/initStruct/02 下面配置第二个仓库结构模板
如果在web中创建时，如何选用默认的 /home/svnadmin/templete/initStruct/02 下面的仓库结构模板？
【解决方案】
由于时间问题，开发时并没有对此功能做更多的详细开发，因此只预留了配置文件层面的修改途径，后续会将仓库模板功能加入到web配置，无需手动命令行管理
可以通过修改 config/svn.php 中的 templete_init_struct_01 值来修改
```

### 13、docker版本要修改容器内 svn 的 3690 默认端口 

```
【解释】 
既然使用 docker 版本，则无需考虑容器内应用的端口，因为可通过容器启动时候做端口映射 
docker版本因为处于容器中权限问题禁用了一些按钮的操作权限，如修改svn服务的端口和绑定主机等信息 
假如启动容器时，映射关系为 3691:3690 表示宿主机3691映射到容器的3690，因此在容器中修改3690为3692，会导致宿主机的3691无法提供服务 
后面会改进 docker 版本，尽量令使用体验跟原生机器一致 
【修改端口方案】 
法1 
直接在容器启动时即指定宿主机的映射端口，如 3692:3690 这样在容器中的管理系统查看还是3690，但是宿主机通过 3692 提供svn服务 
法2（通过提供的dockefile自己重构docker镜像） 
修改所有文件中的3690端口为想要的端口如3692 
之后通过 docker build . -t svnadmin:xxx-edit 即可得到标签为 svnadmin:xxx-edit 的自定义构建镜像 
这样的做法好处为管理系统查看到的端口为3692，启动docker时候映射端口的写法也可为 3692:3692
```

### 14、如何创建其它的管理员账户 ?

```
由于目前的管理系统版本没有考虑到多用户权限管理的问题 ，此问题将在后续版本加入多用户权限管理解决
如果需要多个不同的管理员账号可以通过向管理员表 admin_users 手动插入数据 
使用sqlite：数据库文件位置 /home/svnadmin/svnadmind.db，如果不熟悉sqlite的命令行插入，可以下载该文件到本地，使用 navicat 系列数据库管理软件打开修改，之后覆盖到服务器 
使用mysql：进入命令行手动修改
```

### 15、配置了自定义仓库模板但是创建仓库时没有生效

```
注意配置自定义仓库模板的位置 
通常的位置在 /home/svnadmin/templete/initStruct/01 下面 
而不是在项目代码相关的位置
```

### 16、数据长度超过8192 请向上调整参数：SOCKET_READ_LENGTH

```
【出现问题原因】
svn的用户量和权限配置数量增加，超过了默认值
【解决方案】
修改 config/daemon.php 文件中的 SOCKET_READ_LENGTH 和 SOCKET_WRITE_LENGTH 
设置到133693415 字节也就是大约小于128M貌似都是可以的，再大没有测试过
修改后别忘记要重启守护进程，重启守护进程的方式根据安装方式的不同而不同（不重启会出问题）
【适用版本】
2.1.0+
```

### 17、登录时二维码总是提示输入错误

```
【出现问题原因】 
首次登录数据信息默认使用sqlite数据库 
由于部署问题或其它问题造成数据库文件 /home/svnadmin/svnadmin.db 没有权限 
【解决方案】 
为sqlite数据库文件和文件所在目录授权777 
chmod 777 /home/svnadmin/svnadmin.db 
chmod 777 -R /home/svnadmin 
如果是容器部署，需要在容器中执行此操作而不是在宿主机执行
```

### 18、关于修改数据存储主目录后的升级

```
修改过数据存储主目录的，升级新版本程序需要注意：
升级后程序的配置还是默认的目录路径不是修改过的路径
需要做以下修改
1、停止后台程序 php server/svnadmind.php stop
2、修改配置文件 config/svn.php 中的一处默认路径 /home/svnadmin/ 为自己配置的路径
3、停止 svnserve 服务 systemctl stop svnserve 或者直接 kill pid
4、修改 svnserve 的服务文件中的两处默认路径 /home/svnadmin/ 为自己配置的路径
5、启动后台程序和启动 svnserve 服务

会在下个版本简化升级步骤并解决此问题
```

### 19、本程序的工作模式

- 通过使 svnadmind.php 成为守护进程并监听指定端口来工作
- php-fpm与php-cli程序的使用TCP套接字通信

  ![](./00.static/03.daemon/work.png)

## 五、功能介绍

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
