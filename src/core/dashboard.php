<?php
require_once 'cache.class.php';
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 检查数据库连接文件是否存在
if (!file_exists('conn.php')) {
    echo json_encode(array('success' => false, 'message' => '数据库连接文件不存在'));
    exit;
}

// 包含数据库连接文件
require_once 'conn.php';

// 检查操作类文件是否存在
if (!file_exists('operate.class.php')) {
    echo json_encode(array('success' => false, 'message' => '操作类文件不存在'));
    exit;
}

// 包含操作类文件
require_once 'operate.class.php';

header('Content-Type: application/json');

// 检查用户是否已登录
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(array('success' => false, 'message' => '未登录'));
    exit;
}

// 检查PDO对象是否可用
if (!isset($pdo) || !($pdo instanceof PDO)) {
    echo json_encode(array('success' => false, 'message' => '数据库连接对象不可用'));
    exit;
}

try {
    // 尝试从缓存获取仪表盘数据
    $cacheKey = "dashboard_data_" . $_SESSION['admin_id'];
    $cachedData = $cache->get($cacheKey, 'dashboard');
    if ($cachedData !== null) {
        echo json_encode($cachedData);
        exit;
    }

    // 创建操作对象
    $operate = new Operate($pdo);
    
    // 获取管理员信息
    $adminInfo = $operate->getAdminInfo();
    if (!$adminInfo) {
        echo json_encode(array('success' => false, 'message' => '无法获取管理员信息'));
        exit;
    }

    // 获取账号总数
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM tb_accountInfo WHERE is_deleted = 0");
        $totalAccounts = $stmt->fetchColumn();
    } catch (PDOException $e) {
        echo json_encode(array('success' => false, 'message' => '获取账号总数失败: ' . $e->getMessage()));
        exit;
    }

    // 获取回收站项目数
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM tb_accountInfo WHERE is_deleted = 1");
        $recycleBinItems = $stmt->fetchColumn();
    } catch (PDOException $e) {
        echo json_encode(array('success' => false, 'message' => '获取回收站项目数失败: ' . $e->getMessage()));
        exit;
    }

    // 获取备份文件数
    $backupFiles = count($operate->getBackupFiles());

    // 模拟获取访问量数据（过去7天）
    $visitorData = array(
        'labels' => array(),
        'values' => array()
    );

    for ($i = 6; $i >= 0; $i--) {
        $date = date('m-d', strtotime("-$i days"));
        $visitorData['labels'][] = $date;
        $visitorData['values'][] = rand(50, 200); // 模拟数据
    }

    // 模拟获取服务器压力数据（过去6小时）
    $serverLoadData = array(
        'labels' => array(),
        'cpu' => array(),
        'memory' => array()
    );
    for ($i = 5; $i >= 0; $i--) {
        $hour = date('H:i', strtotime("-$i hours"));
        $serverLoadData['labels'][] = $hour;
        $serverLoadData['cpu'][] = rand(20, 80); // 模拟CPU使用率
        $serverLoadData['memory'][] = rand(30, 90); // 模拟内存使用率
    }

    // 如果没有头像，设置默认头像
    if (empty($adminInfo['avatar'])) {
        $adminInfo['avatar'] = 'view/images/default-avatar.png';
    }

    $result = array(
        'success' => true,
        'admin' => $adminInfo,
        'totalAccounts' => $totalAccounts,
        'recycleBinItems' => $recycleBinItems,
        'backupFiles' => $backupFiles,
        'visitorData' => $visitorData,
        'serverLoadData' => $serverLoadData
    );

    // 缓存仪表盘数据10分钟
    $cache->set($cacheKey, $result, 'dashboard', 600);

    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(array('success' => false, 'message' => '获取仪表盘数据失败: ' . $e->getMessage()));
}
?>