-- MySQL dump 10.19  Distrib 10.3.28-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: svnadmin
-- ------------------------------------------------------
-- Server version	10.3.28-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_name` varchar(100) NOT NULL,
  `config_value` varchar(500) DEFAULT NULL,
  `config_ps` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COMMENT='记录系统中可能被经常修改的配置项';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `config`
--

LOCK TABLES `config` WRITE;
/*!40000 ALTER TABLE `config` DISABLE KEYS */;
INSERT INTO `config` VALUES (1,'BACKUP_PATH','/var/backup','备份文件存储路径'),(2,'PROTOCOL','http','协议类型'),(3,'SERVER_DOMAIN','localhost','服务器域名'),(4,'SERVER_IP','127.0.0.1','服务器IP地址'),(5,'SVN_PORT','3690','SVN服务的端口'),(6,'SVN_REPOSITORY_PATH','/www/svn','项目仓库的父路径'),(7,'SVN_WEB_PATH','/repository','SVN目录浏览的根名称'),(8,'ALL_MAIL_STATUS','0','系统是否启用邮件服务'),(9,'mod_dav_svn_status','0','系统是否支持开启目录浏览');
/*!40000 ALTER TABLE `config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crontab`
--

DROP TABLE IF EXISTS `crontab`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `crontab` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `backup_type` varchar(45) NOT NULL DEFAULT '备份类型 dump 或 hotcopy',
  `cycle_type` varchar(45) NOT NULL DEFAULT '执行周期 weekly daily hourly',
  `week` varchar(45) NOT NULL DEFAULT '周几执行 1 2 3 4 5 6 7',
  `hour` varchar(45) NOT NULL DEFAULT '哪个小时执行 0-24',
  `minute` varchar(45) NOT NULL DEFAULT '哪分钟执行 0-60',
  `repository_name` varchar(45) NOT NULL DEFAULT '操作仓库名称',
  `crontab_count` varchar(45) NOT NULL DEFAULT '备份保存数量',
  `sign` varchar(45) NOT NULL DEFAULT '计划任务标识',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crontab`
--

LOCK TABLES `crontab` WRITE;
/*!40000 ALTER TABLE `crontab` DISABLE KEYS */;
/*!40000 ALTER TABLE `crontab` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `log`
--

DROP TABLE IF EXISTS `log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `content` varchar(45) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `browser` varchar(45) DEFAULT NULL,
  `os` varchar(45) DEFAULT NULL,
  `time` varchar(45) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='系统日志表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log`
--

LOCK TABLES `log` WRITE;
/*!40000 ALTER TABLE `log` DISABLE KEYS */;
/*!40000 ALTER TABLE `log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mail`
--

DROP TABLE IF EXISTS `mail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `protocol_type` varchar(45) NOT NULL COMMENT '邮件服务类型',
  `mail_host` varchar(1000) NOT NULL COMMENT '邮件服务器主机地址',
  `mail_port` int(11) NOT NULL COMMENT '邮件服务器端口',
  `mail_ssl_port` int(11) DEFAULT NULL COMMENT '邮件服务器SSL端口',
  `mail_user` varchar(1000) NOT NULL COMMENT '邮件服务器用户名',
  `mail_password` varchar(1000) NOT NULL COMMENT '邮件服务器密码',
  `send_mail` varchar(1000) NOT NULL COMMENT '邮件发送人',
  `single_mail_status` int(1) NOT NULL COMMENT '该条邮件服务器记录是否启用',
  `add_time` varchar(1000) NOT NULL COMMENT '记录添加时间',
  `ps` varchar(1000) DEFAULT NULL COMMENT '其它说明',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='邮件服务器记录表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mail`
--

LOCK TABLES `mail` WRITE;
/*!40000 ALTER TABLE `mail` DISABLE KEYS */;
INSERT INTO `mail` VALUES (1,'SMTP','smtp.qq.com',587,0,'1801168257','fybbookmsiuvdaeg','1801168257@qq.com',1,'2021-09-16-00-28-06','');
/*!40000 ALTER TABLE `mail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `repository`
--

DROP TABLE IF EXISTS `repository`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repository` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `repository_name` varchar(1000) NOT NULL,
  `repository_url` varchar(1000) DEFAULT NULL,
  `repository_checkout_url` varchar(1000) DEFAULT NULL,
  `repository_web_url` varchar(1000) DEFAULT NULL,
  `repository_size` double DEFAULT NULL,
  `repository_edittime` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='仓库表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repository`
--

LOCK TABLES `repository` WRITE;
/*!40000 ALTER TABLE `repository` DISABLE KEYS */;
/*!40000 ALTER TABLE `repository` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role`
--

DROP TABLE IF EXISTS `role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rolename` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT='用户角色表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role`
--

LOCK TABLES `role` WRITE;
/*!40000 ALTER TABLE `role` DISABLE KEYS */;
INSERT INTO `role` VALUES (1,'超级管理员'),(2,'系统管理员'),(3,'普通用户');
/*!40000 ALTER TABLE `role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `roleid` int(11) NOT NULL,
  `username` varchar(45) DEFAULT NULL,
  `password` varchar(45) DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `last_login_time` varchar(45) DEFAULT NULL,
  `email` varchar(45) DEFAULT NULL,
  `phone` varchar(45) DEFAULT NULL,
  `realname` varchar(45) DEFAULT NULL,
  `add_time` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8 COMMENT='用户表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1,1,'administrator','administrator',NULL,NULL,'1801168257@qq.com','','超级管理员','0000-00-00-00-00-00');
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_repository`
--

DROP TABLE IF EXISTS `user_repository`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_repository` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `repositoryid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户-角色表 保存了每个普通用户被授权使用的仓库列表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_repository`
--

LOCK TABLES `user_repository` WRITE;
/*!40000 ALTER TABLE `user_repository` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_repository` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2021-09-16  0:40:32
