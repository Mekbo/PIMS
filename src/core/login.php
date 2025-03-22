<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'conn.php';
require_once 'operate.class.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = @$_POST['username'] ? $_POST['username'] :'';
    $password = @$_POST['password'] ? $_POST['password'] : '';

    if (empty($username) || empty($password)) {
        echo json_encode(array('success' => false, 'message' => '用户名和密码不能为空'));
        exit;
    }

    $stmt = $pdo->prepare("SELECT admin_id, username, password FROM tb_admin WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && md5($password) === $user['password']) {
        $_SESSION['admin_id'] = $user['admin_id'];
        $_SESSION['username'] = $user['username'];
        $response = array('success' => true);       
        echo json_encode($response);
    } else {
        $response = array('success' => false, 'message' => '用户名或密码错误');
        echo json_encode($response);
    }
} else {
    $response = array('success' => false, 'message' => '无效的请求方法');
    echo json_encode($response);
}
?>