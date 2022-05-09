/*
 Navicat Premium Data Transfer

 Source Server         : svnadmin.sqlite.struct
 Source Server Type    : SQLite
 Source Server Version : 3030001
 Source Schema         : main

 Target Server Type    : SQLite
 Target Server Version : 3030001
 File Encoding         : 65001

 Date: 09/05/2022 14:15:16
*/

PRAGMA foreign_keys = false;

-- ----------------------------
-- Table structure for admin_users
-- ----------------------------
DROP TABLE IF EXISTS "admin_users";
CREATE TABLE "admin_users" (
  "admin_user_id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  "admin_user_name" TEXT(45) NOT NULL,
  "admin_user_password" TEXT(45) NOT NULL,
  "admin_user_phone" TEXT(11),
  "admin_user_email" TEXT
);

-- ----------------------------
-- Records of admin_users
-- ----------------------------
INSERT INTO "admin_users" VALUES (1, 'admin', 'admin', NULL, NULL);

-- ----------------------------
-- Table structure for black_token
-- ----------------------------
DROP TABLE IF EXISTS "black_token";
CREATE TABLE "black_token" (
  "token_id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  "token" TEXT(200) NOT NULL,
  "start_time" TEXT(45) NOT NULL,
  "end_time" TEXT(45) NOT NULL,
  "insert_time" TEXT(45) NOT NULL
);

-- ----------------------------
-- Records of black_token
-- ----------------------------

-- ----------------------------
-- Table structure for logs
-- ----------------------------
DROP TABLE IF EXISTS "logs";
CREATE TABLE "logs" (
  "log_id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  "log_type_name" TEXT(200) NOT NULL,
  "log_content" TEXT(5000),
  "log_add_user_name" TEXT(200) NOT NULL,
  "log_add_time" TEXT(45) NOT NULL
);

-- ----------------------------
-- Records of logs
-- ----------------------------

-- ----------------------------
-- Table structure for options
-- ----------------------------
DROP TABLE IF EXISTS "options";
CREATE TABLE "options" (
  "option_id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  "option_name" text NOT NULL,
  "option_value" text NOT NULL,
  "option_description" TEXT
);

-- ----------------------------
-- Records of options
-- ----------------------------

-- ----------------------------
-- Table structure for svn_groups
-- ----------------------------
DROP TABLE IF EXISTS "svn_groups";
CREATE TABLE "svn_groups" (
  "svn_group_id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  "svn_group_name" TEXT(200) NOT NULL,
  "include_user_count" integer NOT NULL,
  "include_group_count" integer NOT NULL,
  "svn_group_note" TEXT(1000)
);

-- ----------------------------
-- Records of svn_groups
-- ----------------------------

-- ----------------------------
-- Table structure for svn_reps
-- ----------------------------
DROP TABLE IF EXISTS "svn_reps";
CREATE TABLE "svn_reps" (
  "rep_id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  "rep_name" TEXT(1000) NOT NULL,
  "rep_size" integer,
  "rep_rev" integer,
  "rep_uuid" text,
  "rep_note" TEXT(1000)
);

-- ----------------------------
-- Records of svn_reps
-- ----------------------------

-- ----------------------------
-- Table structure for svn_user_pri_paths
-- ----------------------------
DROP TABLE IF EXISTS "svn_user_pri_paths";
CREATE TABLE "svn_user_pri_paths" (
  "svnn_user_pri_path_id" INTEGER NOT NULL,
  "rep_name" TEXT NOT NULL,
  "pri_path" TEXT NOT NULL,
  "rep_pri" TEXT,
  "svn_user_name" TEXT NOT NULL,
  "unique" TEXT NOT NULL,
  PRIMARY KEY ("svnn_user_pri_path_id")
);

-- ----------------------------
-- Records of svn_user_pri_paths
-- ----------------------------

-- ----------------------------
-- Table structure for svn_users
-- ----------------------------
DROP TABLE IF EXISTS "svn_users";
CREATE TABLE "svn_users" (
  "svn_user_id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  "svn_user_name" TEXT(200) NOT NULL,
  "svn_user_pass" TEXT(200) NOT NULL,
  "svn_user_status" integer(1) NOT NULL,
  "svn_user_note" TEXT(1000)
);

-- ----------------------------
-- Records of svn_users
-- ----------------------------

-- ----------------------------
-- Table structure for verification_code
-- ----------------------------
DROP TABLE IF EXISTS "verification_code";
CREATE TABLE "verification_code" (
  "code_id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  "uuid" text(45) NOT NULL,
  "code" TEXT(45) NOT NULL,
  "start_time" TEXT(45) NOT NULL,
  "end_time" TEXT(45) NOT NULL,
  "insert_time" TEXT(45) NOT NULL
);

-- ----------------------------
-- Records of verification_code
-- ----------------------------

-- ----------------------------
-- Table structure for sqlite_sequence
-- ----------------------------
DROP TABLE IF EXISTS "sqlite_sequence";
CREATE TABLE "sqlite_sequence" (
  "name",
  "seq"
);

-- ----------------------------
-- Records of sqlite_sequence
-- ----------------------------
INSERT INTO "sqlite_sequence" VALUES ('admin_users', 1);

-- ----------------------------
-- Auto increment value for admin_users
-- ----------------------------
UPDATE "sqlite_sequence" SET seq = 1 WHERE name = 'admin_users';

PRAGMA foreign_keys = true;
