<?php
session_start();
require_once 'conn.php';
require_once 'operate.class.php';

header('Content-Type: application/json');

// 检查用户是否已登录
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(array('success' => false, 'message' => '未登录'));
    exit;
}

$operate = new Operate($pdo);
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'list':
        $page = intval(isset($_GET['page']) ? $_GET['page'] : 1);
        $perPage = intval(isset($_GET['perPage']) ? $_GET['perPage'] : 20);
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        
        $result = $operate->getAccounts($page, $perPage, $search, 1);
        $response = array('success' => true);
        foreach ($result as $key => $value) {
            $response[$key] = $value;
        }
        echo json_encode($response);
        break;
        
    case 'restore':
        $ids = json_decode(isset($_POST['ids']) ? $_POST['ids'] : '[]', true);
        if (empty($ids)) {
            echo json_encode(array('success' => false, 'message' => '未选择要恢复的账号'));
            break;
        }
        
        if ($operate->restoreFromRecycleBin($ids)) {
            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('success' => false, 'message' => '恢复账号失败'));
        }
        break;	
        
    case 'delete':
        $ids = json_decode(isset($_POST['ids']) ? $_POST['ids'] : '[]', true);
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        
        if (empty($ids)) {
            echo json_encode(array('success' => false, 'message' => '未选择要删除的账号'));
            break;
        }
        
        if (empty($password)) {
            echo json_encode(array('success' => false, 'message' => '请输入管理员密码'));
            break;
        }
        
        if (!$operate->verifyAdminPassword($password)) {
            echo json_encode(array('success' => false, 'message' => '管理员密码错误'));
            break;
        }
        
        if ($operate->permanentDelete($ids)) {
            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('success' => false, 'message' => '彻底删除账号失败'));
        }
        break;
        
    default:
        echo json_encode(array('success' => false, 'message' => '无效的操作'));
        break;
}
?>