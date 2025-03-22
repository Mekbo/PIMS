<?php
session_start();

// 检查系统是否已安装
if (!file_exists(__DIR__ . '/install/install.lock') && !file_exists(__DIR__ . '/core/config.php')) {
    // 系统未安装，重定向到安装页面
    header('Location: install/index.php');
    exit;
}

// 检查用户是否已登录
$isLoggedIn = isset($_SESSION['admin_id']);

// 获取请求的页面
$page = @$_GET['page'] ? $_GET['page'] :'';

// 如果用户未登录且请求的不是登录页面，则重定向到登录页面
if (!$isLoggedIn && $page !== 'login') {
    $page = 'login';
}

// 根据页面名称加载相应的HTML内容
switch ($page) {
    case 'login':
        include 'view/login.html';
        break;
    case 'main':
        include 'view/main.html';
        break;
    case 'accountManage':
        include 'view/accountManage.html';
        break;
    case 'backupRestore':
        include 'view/backupRestore.html';
        break;
    case 'recycleBin':
        include 'view/recycleBin.html';
        break;
    case 'systemSettings':
        include 'view/systemSettings.html';
        break;
    default:
        // 如果用户已登录，默认显示主页面；否则显示登录页面
        if ($isLoggedIn) {
            include 'view/main.html';
        } else {
            include 'view/login.html';
        }
        break;
}
?>