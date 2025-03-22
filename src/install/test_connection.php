<?php
// 测试数据库连接
header('Content-Type: application/json');

$host = $_POST['host'] ? $_POST['host'] : '';
$port = $_POST['port'] ? $_POST['port'] : '3306';
$username = $_POST['username'] ? $_POST['username'] : '';
$password = $_POST['password'] ? $_POST['password'] : '';
$database = $_POST['database'] ? $_POST['database'] : '';

if (empty($host) || empty($username)) {
    echo json_encode(['status' => false, 'message' => '请填写主机地址和用户名']);
    exit;
}

try {
    // 尝试连接数据库
    $dsn = "mysql:host={$host};port={$port}";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 检查数据库是否存在
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$database}'");
    $dbExists = $stmt->fetchColumn();
    
    if ($dbExists) {
        echo json_encode(['status' => true, 'message' => "连接成功！数据库 {$database} 已存在。"]);
    } else {
        echo json_encode(['status' => true, 'message' => "连接成功！数据库 {$database} 不存在，安装程序将自动创建。"]);
    }
    
} catch (PDOException $e) {
    echo json_encode(['status' => false, 'message' => '数据库连接失败：' . $e->getMessage()]);
}