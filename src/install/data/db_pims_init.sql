-- 创建数据库
CREATE DATABASE IF NOT EXISTS db_pims DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- 使用数据库
USE db_pims;

-- 创建管理员用户信息表
CREATE TABLE IF NOT EXISTS tb_admin (
    admin_id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(50) NOT NULL,
    nickname VARCHAR(50) DEFAULT NULL,
    avatar VARCHAR(100) DEFAULT NULL,
    PRIMARY KEY (admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 创建网络账号信息表
CREATE TABLE IF NOT EXISTS tb_accountInfo (
    account_id INT(11) NOT NULL AUTO_INCREMENT,
    account_name VARCHAR(50) NOT NULL,
    account_password VARCHAR(100) NOT NULL,
    nickname VARCHAR(50) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    email VARCHAR(50) DEFAULT NULL,
    security_question TEXT(512) DEFAULT NULL,
    platform VARCHAR(50) DEFAULT NULL,
    url VARCHAR(128) DEFAULT NULL,
    register_date DATE DEFAULT NULL,
    remarks TEXT,
    is_deleted TINYINT(1) DEFAULT 0,
    PRIMARY KEY (account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 插入默认管理员账号
INSERT INTO tb_admin (username, password, nickname, avatar) 
VALUES ('admin', 'e10adc3949ba59abbe56e057f20f883e', 'Administrator', 'view/images/default_avatar.png');