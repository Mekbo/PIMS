<?php
require_once 'cache.class.php';
session_start();
require_once 'conn.php';
require_once 'operate.class.php';

header('Content-Type: application/json');

// 检查用户是否已登录
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(array('success' => false, 'message' => '未登录'));
    exit;
}

global $cache;
$operate = new Operate($pdo);
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'getAdminInfo':
        // 尝试从缓存获取管理员信息
        $cacheKey = "admin_info_" . $_SESSION['admin_id'];
        $adminInfo = $cache->get($cacheKey, 'system');
        
        if ($adminInfo === null) {
            $adminInfo = $operate->getAdminInfo();
            if ($adminInfo) {
                // 缓存管理员信息30分钟
                $cache->set($cacheKey, $adminInfo, 'system', 1800);
            }
        }
        
        if ($adminInfo) {
            echo json_encode(array('success' => true, 'admin' => $adminInfo));
        } else {
            echo json_encode(array('success' => false, 'message' => '获取管理员信息失败'));
        }
        break;
        
    case 'updateAdminInfo':
        $data = array(
            'nickname' => isset($_POST['nickname']) ? $_POST['nickname'] : ''
        );
        
        // 处理头像上传
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
            $uploadDir = '../view/images/';
            $fileExtension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $newFileName = 'avatar_' . time() . '.' . $fileExtension;
            $uploadFile = $uploadDir . $newFileName;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadFile)) {
                $data['avatar'] = 'view/images/' . $newFileName;
            } else {
                echo json_encode(array('success' => false, 'message' => '头像上传失败'));
                break;
            }
        }
        
        if ($operate->updateAdminInfo($data)) {
            // 清除管理员信息缓存
            $cache->delete("admin_info_" . $_SESSION['admin_id'], 'system');
            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('success' => false, 'message' => '更新管理员信息失败'));
        }
        break;
        
    case 'updatePassword':
        $currentPassword = isset($_POST['currentPassword']) ? $_POST['currentPassword'] : '';
        $newPassword = isset($_POST['newPassword']) ? $_POST['newPassword'] : '';
        
        if (empty($currentPassword) || empty($newPassword)) {
            echo json_encode(array('success' => false, 'message' => '当前密码和新密码不能为空'));
            break;
        }
        
        if (!$operate->verifyAdminPassword($currentPassword)) {
            echo json_encode(array('success' => false, 'message' => '当前密码错误'));
            break;
        }
        
        if ($operate->updateAdminInfo(array('password' => $newPassword))) {
            // 清除管理员信息缓存
            $cache->delete("admin_info_" . $_SESSION['admin_id'], 'system');
            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('success' => false, 'message' => '更新密码失败'));
        }
        break;
        
    case 'getBackupSettings':
        // 尝试从缓存获取备份设置
        $cacheKey = "backup_settings";
        $backupPath = $cache->get($cacheKey, 'system');
        
        if ($backupPath === null) {
            $backupPath = $operate->getBackupPath();
            // 缓存备份设置1小时
            $cache->set($cacheKey, $backupPath, 'system', 3600);
        }
        
        echo json_encode(array('success' => true, 'backupPath' => $backupPath));
        break;
        
    case 'updateBackupSettings':
        $backupPath = isset($_POST['backupPath']) ? $_POST['backupPath'] : '';
        if (empty($backupPath)) {
            echo json_encode(array('success' => false, 'message' => '备份路径不能为空'));
            break;
        }
        
        if ($operate->setBackupPath($backupPath)) {
            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('success' => false, 'message' => '更新备份设置失败'));
        }
        break;

    case 'clearCache':
        try {
            $type = isset($_POST['type']) ? $_POST['type'] : '';
            
            if ($type === 'all') {
                // 清除所有类型的缓存
                $cache->clear('accounts');
                $cache->clear('system');
                $cache->clear('dashboard');
                echo json_encode(array('success' => true, 'message' => '所有缓存已清除'));
            } else if (in_array($type, ['accounts', 'system', 'dashboard'])) {
                // 清除指定类型的缓存
                $cache->clear($type);
                echo json_encode(array('success' => true, 'message' => $type . '缓存已清除'));
            } else {
                echo json_encode(array('success' => false, 'message' => '无效的缓存类型'));
            }
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'message' => '清除缓存失败：' . $e->getMessage()));
        }
        break;
        break;
        
    default:
        echo json_encode(array('success' => false, 'message' => '无效的操作'));
        break;
}
?>