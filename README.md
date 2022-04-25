# SVNAdmin 系统部署与使用手册
1、该系统为使用PHP开发的基于web的Subversion（SVN）服务器端管理工具

2、支持操作系统：CentOS7、CentOS8（其它Linux发行版正在测试兼容中）

3、支持PHP版本：PHP5.4+

4、支持数据库：SQLite、MySQL

5、支持Subversion：1.8+

6、支持功能：SVN仓库管理、SVN用户管理、SVN分组管理、目录授权、目录浏览、Hooks管理、在线dump备份、在线备份恢复、SVN用户禁用、服务器状态管理、日志管理、消息通知、更新检测...

7、旧版本演示地址：http://svnadmin.witersen.com (默认的用户名与密码都为 administrator)

8、GitHub地址：https://github.com/witersen/SvnAdminV2.0 Gitee地址：https://gitee.com/witersen/SvnAdminV2.0

9、可以加入群聊讨论遇到的问题  QQ群 633108141

## 一 、新版本v2.3介绍（测试中）

- 登录界面可分角色登录，配合后端实现的登录验证码更安全

  ![](./00.static/01.images/01.jpg)
  
- 服务器状态监控和信息统计，对当前服务器状态和SVN仓库信息更加了解

  ![](./00.static/01.images/02.jpg)
  
- SVN仓库概览，提供了多种高级功能，还可根据仓库名、版本数、体积等一键排序

  ![](./00.static/01.images/03.jpg)
  
- 新建SVN仓库提供两种模式 随意选择

  ![](./00.static/01.images/04.jpg)
  
- 在线目录浏览更加方便 逐层加载，服务资源占用更低

  ![](./00.static/01.images/05.jpg)
  
- 通过目录面包屑可以随时回到某级目录 可以看到目录的作者、版本、提交日期、提交日志等，还可一键复制检出地址

  ![](./00.static/01.images/06.jpg)
  
- 仓库授权精确到文件级别，可对用户和用户组快速授权和更改权限

  ![](./00.static/01.images/07.jpg)
  
- 支持在线dump方式备份，备份文件可随时下载或删除

  ![](./00.static/01.images/08.jpg)
  
- 支持对SVN仓库钩子的管理

  ![](./00.static/01.images/09.jpg)
  
- 可以查看每个仓库的详细信息，一键复制详细信息更加方便

  ![](./00.static/01.images/10.jpg)
  
- 可以将通过dump方式备份的文件再导入仓库 实现SVN仓库的迁移

  ![](./00.static/01.images/11.jpg)
  
- 仓库导入过程中如果抛出了错误信息会被收集被显示 方便管理人员更好的定位问题

  ![](./00.static/01.images/12.jpg)
  
- 可以很方便的修改仓库名称，这会自动同步配置文件，所以无需担心

  ![](./00.static/01.images/13.jpg)
  
- 删除仓库也会有风险提示

  ![](./00.static/01.images/14.jpg)
  
- SVN用户管理支持启用、禁用、添加备注信息，管理用户更加方便

  ![](./00.static/01.images/15.jpg)
  
- SVN分组支持显示其包含的用户和分组数量 同时支持修改备注信息

  ![](./00.static/01.images/16.jpg)
  
- 可以对分组进行用户成员编辑，系统会贴心的提示用户是否处于禁用状态

  ![](./00.static/01.images/17.jpg)
  
- 用户管理支持组嵌套，同时如果你不小心搞了一个循环嵌套，系统会提示你

  ![](./00.static/01.images/18.jpg)
  
- 分组名支持修改，系统会帮你修改配置文件的一切，无需担心

  ![](./00.static/01.images/19.jpg)
  
- 系统提供了日志功能，可以对系统的运转情况做个记录啦

  ![](./00.static/01.images/20.jpg)
  
- 管理人员可以修改自己的账号和密码，更加安全

  ![](./00.static/01.images/21.jpg)
  
- 系统提供了svnserve主机和端口绑定功能 而且开启了svnserve的运行日志 为你多一层运维保障

  ![](./00.static/01.images/22.jpg)
  
- 看看你的数据都在哪里存储呢

  ![](./00.static/01.images/23.jpg)
  
- 配置邮件通知服务吧

  ![](./00.static/01.images/24.jpg)
  
- 风险操作可以来个提示

  ![](./00.static/01.images/25.jpg)
  
- 定期修改密钥更安全

  ![](./00.static/01.images/26.jpg)
  
- 看看有没有新版本吧

  ![](./00.static/01.images/27.jpg)
  
- SVN用户个人的界面 只可看到被授权的仓库路径

  ![](./00.static/01.images/28.jpg)
  
- SVN用户可自己修改密码 无需联系管理人员了

  ![](./00.static/01.images/29.jpg)

## 二、将已有的SVN仓库使用此系统管理

- （1）安装本系统
- （2）通过系统服务管理界面安装Subversion
- （3）将已有的一个或多个SVN仓库移动到 /home/svnadmin/rep/ 目录下 
- （4）刷新管理系统的仓库管理页面即可识别SVN仓库
- （5）注意此方式并不会识别SVN仓库原有的用户以及权限配置，因为我们使用了统一的配置文件来进行用户和权限管理，因此迁移仓库后还需要在管理系统重新添加用户、用户组、配置权限！