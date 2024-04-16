-- MySQL dump 10.13  Distrib 5.6.50, for Linux (x86_64)
--
-- Host: localhost    Database: svnadmin
-- ------------------------------------------------------
-- Server version	5.6.50-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin_users`
--

DROP TABLE IF EXISTS `admin_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_users` (
  `admin_user_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户id',
  `admin_user_name` varchar(45) NOT NULL COMMENT '用户名',
  `admin_user_password` varchar(45) NOT NULL COMMENT '用户密码',
  `admin_user_phone` char(11) DEFAULT NULL COMMENT '用户手机号',
  `admin_user_email` varchar(45) DEFAULT NULL COMMENT '用户邮箱',
  `admin_user_token` varchar(255) DEFAULT NULL COMMENT '用户当前token',
  PRIMARY KEY (`admin_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='管理系统用户';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_users`
--

LOCK TABLES `admin_users` WRITE;
/*!40000 ALTER TABLE `admin_users` DISABLE KEYS */;
INSERT INTO `admin_users` VALUES (1,'admin','admin',NULL,NULL,NULL);
/*!40000 ALTER TABLE `admin_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `black_token`
--

DROP TABLE IF EXISTS `black_token`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `black_token` (
  `token_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'tokenid',
  `token` varchar(200) NOT NULL COMMENT 'token内容',
  `start_time` varchar(45) NOT NULL COMMENT 'token的生效时间',
  `end_time` varchar(45) NOT NULL COMMENT 'token的失效时间',
  `insert_time` varchar(45) NOT NULL COMMENT '注销时间',
  PRIMARY KEY (`token_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='黑名单token 意思为用户注销后尚未达到过期时间的token将会被加入此黑名单 通过定时的主动扫描来去除过期的token 达到注销即安全的目的';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `black_token`
--

LOCK TABLES `black_token` WRITE;
/*!40000 ALTER TABLE `black_token` DISABLE KEYS */;
/*!40000 ALTER TABLE `black_token` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crond`
--

DROP TABLE IF EXISTS `crond`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `crond` (
  `crond_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sign` varchar(45) NOT NULL COMMENT 'shell文件和日志文件唯一标识',
  `task_type` int(11) unsigned NOT NULL COMMENT '任务计划类型\r\n\r\n1 仓库备份[dump-全量]\r\n2 仓库备份[dump-增量]\r\n3 仓库备份[hotcopy-全量]\r\n4 仓库备份[hotcopy-增量]\r\n5 仓库检查\r\n6 shell脚本',
  `task_name` varchar(450) NOT NULL COMMENT '任务名称',
  `cycle_type` varchar(45) NOT NULL COMMENT '周期类型\r\n\r\nminute 每分钟\r\nminute_n 每隔N分钟\r\nhour 每小时\r\nhour_n 每隔N小时\r\nday 每天\r\nday_n 每隔N天\r\nweek 每周\r\nmonth 每月',
  `cycle_desc` varchar(450) NOT NULL COMMENT '执行周期描述',
  `status` int(11) unsigned NOT NULL COMMENT '启用状态',
  `save_count` int(11) unsigned NOT NULL COMMENT '保存数量',
  `rep_name` varchar(255) DEFAULT NULL COMMENT '操作仓库列表',
  `week` int(11) unsigned DEFAULT NULL COMMENT '周',
  `day` int(11) unsigned DEFAULT NULL COMMENT '天或日',
  `hour` int(11) unsigned DEFAULT NULL COMMENT '小时',
  `minute` int(11) unsigned DEFAULT NULL COMMENT '分钟',
  `notice` int(11) unsigned NOT NULL COMMENT '0 关闭通知 1 成功通知 2 失败通知 3 全部通知',
  `code` varchar(45) NOT NULL COMMENT '任务计划表达式',
  `shell` mediumtext COMMENT '自定义脚本',
  `last_exec_time` varchar(45) NOT NULL COMMENT '上次执行时间',
  `create_time` varchar(45) NOT NULL COMMENT '添加时间',
  PRIMARY KEY (`crond_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crond`
--

LOCK TABLES `crond` WRITE;
/*!40000 ALTER TABLE `crond` DISABLE KEYS */;
/*!40000 ALTER TABLE `crond` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logs`
--

DROP TABLE IF EXISTS `logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '日志id',
  `log_type_name` varchar(200) NOT NULL COMMENT '日志类型',
  `log_content` varchar(5000) NOT NULL COMMENT '日志内容',
  `log_add_user_name` varchar(200) NOT NULL COMMENT '操作人',
  `log_add_time` varchar(45) NOT NULL COMMENT '操作时间',
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='日志表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs`
--

LOCK TABLES `logs` WRITE;
/*!40000 ALTER TABLE `logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `options`
--

DROP TABLE IF EXISTS `options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `options` (
  `option_id` int(11) NOT NULL AUTO_INCREMENT,
  `option_name` varchar(191) NOT NULL,
  `option_value` longtext NOT NULL,
  `option_description` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`option_id`),
  UNIQUE KEY `option_name_UNIQUE` (`option_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='全局配置项';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `options`
--

LOCK TABLES `options` WRITE;
/*!40000 ALTER TABLE `options` DISABLE KEYS */;
/*!40000 ALTER TABLE `options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subadmin`
--

DROP TABLE IF EXISTS `subadmin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subadmin` (
  `subadmin_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `subadmin_name` varchar(255) NOT NULL,
  `subadmin_password` varchar(255) NOT NULL,
  `subadmin_phone` varchar(255) DEFAULT NULL,
  `subadmin_email` varchar(255) DEFAULT NULL,
  `subadmin_status` int(255) NOT NULL,
  `subadmin_note` varchar(255) DEFAULT NULL,
  `subadmin_last_login` varchar(255) DEFAULT NULL,
  `subadmin_create_time` varchar(20) NOT NULL,
  `subadmin_tree` mediumtext,
  `subadmin_functions` mediumtext,
  `subadmin_token` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`subadmin_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subadmin`
--

LOCK TABLES `subadmin` WRITE;
/*!40000 ALTER TABLE `subadmin` DISABLE KEYS */;
/*!40000 ALTER TABLE `subadmin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `svn_groups`
--

DROP TABLE IF EXISTS `svn_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `svn_groups` (
  `svn_group_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '分组id',
  `svn_group_name` varchar(200) NOT NULL COMMENT '分组名称',
  `include_user_count` int(11) NOT NULL,
  `include_group_count` int(11) NOT NULL,
  `include_aliase_count` int(11) NOT NULL,
  `svn_group_note` varchar(1000) DEFAULT NULL COMMENT '分组备注信息',
  PRIMARY KEY (`svn_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='SVN分组表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `svn_groups`
--

LOCK TABLES `svn_groups` WRITE;
/*!40000 ALTER TABLE `svn_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `svn_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `svn_reps`
--

DROP TABLE IF EXISTS `svn_reps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `svn_reps` (
  `rep_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '仓库id',
  `rep_name` varchar(1000) NOT NULL COMMENT '仓库名称',
  `rep_size` double DEFAULT NULL COMMENT '仓库体积',
  `rep_note` varchar(1000) DEFAULT NULL COMMENT '仓库备注',
  `rep_rev` int(11) DEFAULT NULL COMMENT '仓库修订版本',
  `rep_uuid` varchar(45) DEFAULT NULL COMMENT '仓库UUID',
  PRIMARY KEY (`rep_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='仓库表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `svn_reps`
--

LOCK TABLES `svn_reps` WRITE;
/*!40000 ALTER TABLE `svn_reps` DISABLE KEYS */;
/*!40000 ALTER TABLE `svn_reps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `svn_second_pri`
--

DROP TABLE IF EXISTS `svn_second_pri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `svn_second_pri` (
  `svn_second_pri_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `svnn_user_pri_path_id` int(10) unsigned NOT NULL,
  `svn_object_type` varchar(255) NOT NULL,
  `svn_object_name` varchar(255) NOT NULL,
  PRIMARY KEY (`svn_second_pri_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `svn_second_pri`
--

LOCK TABLES `svn_second_pri` WRITE;
/*!40000 ALTER TABLE `svn_second_pri` DISABLE KEYS */;
/*!40000 ALTER TABLE `svn_second_pri` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `svn_user_pri_paths`
--

DROP TABLE IF EXISTS `svn_user_pri_paths`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `svn_user_pri_paths` (
  `svnn_user_pri_path_id` int(11) NOT NULL AUTO_INCREMENT,
  `rep_name` varchar(1000) NOT NULL COMMENT '仓库名称',
  `pri_path` mediumtext NOT NULL COMMENT '仓库路径',
  `rep_pri` varchar(45) DEFAULT NULL COMMENT '该用户所拥有的权限',
  `svn_user_name` varchar(200) NOT NULL COMMENT '该路径的权限的拥有人',
  `unique` varchar(20000) NOT NULL COMMENT '使用仓库名和路径和权限拼接的唯一值',
  `second_pri` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否可二次授权',
  PRIMARY KEY (`svnn_user_pri_path_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='SVN用户有权限的仓库路径';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `svn_user_pri_paths`
--

LOCK TABLES `svn_user_pri_paths` WRITE;
/*!40000 ALTER TABLE `svn_user_pri_paths` DISABLE KEYS */;
/*!40000 ALTER TABLE `svn_user_pri_paths` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `svn_users`
--

DROP TABLE IF EXISTS `svn_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `svn_users` (
  `svn_user_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户id',
  `svn_user_name` varchar(200) CHARACTER SET utf8 NOT NULL COMMENT '用户名',
  `svn_user_pass` varchar(200) CHARACTER SET utf8 NOT NULL COMMENT '用户密码',
  `svn_user_status` int(1) NOT NULL COMMENT '用户启用状态\n0 禁用\n1 启用',
  `svn_user_note` varchar(1000) CHARACTER SET utf8 DEFAULT NULL COMMENT '用户备注信息',
  `svn_user_last_login` varchar(255) CHARACTER SET utf8 DEFAULT NULL COMMENT '上次登录时间',
  `svn_user_token` varchar(255) CHARACTER SET utf8 DEFAULT NULL COMMENT '用户token',
  `svn_user_mail` varchar(255) CHARACTER SET utf8 DEFAULT NULL COMMENT '用户token',
  PRIMARY KEY (`svn_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='svn用户表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `svn_users`
--

LOCK TABLES `svn_users` WRITE;
/*!40000 ALTER TABLE `svn_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `svn_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tasks` (
  `task_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `task_name` varchar(1000) NOT NULL,
  `task_status` tinyint(1) NOT NULL COMMENT '1 待执行\r\n2 执行中\r\n3 已完成\r\n4 已取消\r\n5 意外中断',
  `task_cmd` varchar(5000) NOT NULL,
  `task_type` varchar(255) NOT NULL,
  `task_unique` varchar(255) NOT NULL,
  `task_log_file` varchar(5000) DEFAULT NULL,
  `task_optional` varchar(5000) DEFAULT NULL,
  `task_create_time` varchar(45) NOT NULL,
  `task_update_time` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `verification_code`
--

DROP TABLE IF EXISTS `verification_code`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `verification_code` (
  `code_id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(45) NOT NULL COMMENT '每次验证码请求的唯一标识',
  `code` varchar(45) NOT NULL COMMENT '验证码',
  `start_time` varchar(45) NOT NULL COMMENT '有效开始时间',
  `end_time` varchar(45) NOT NULL COMMENT '失效时间',
  `insert_time` varchar(45) NOT NULL COMMENT '插入时间',
  PRIMARY KEY (`code_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='验证码';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `verification_code`
--

LOCK TABLES `verification_code` WRITE;
/*!40000 ALTER TABLE `verification_code` DISABLE KEYS */;
/*!40000 ALTER TABLE `verification_code` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'svnadmin'
--

--
-- Dumping routines for database 'svnadmin'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-12-31  0:35:18
