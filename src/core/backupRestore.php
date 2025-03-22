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
    case 'download':
        $filename = isset($_GET['filename']) ? $_GET['filename'] : '';
        if (empty($filename)) {
            echo json_encode(array('success' => false, 'message' => '未指定要下载的备份文件'));
            break;
        }
        
        $filePath = $operate->getBackupPath() . $filename;
        if (!file_exists($filePath)) {
            echo json_encode(array('success' => false, 'message' => '备份文件不存在'));
            break;
        }
        
        // 确保没有之前的输出
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // 设置响应头
        header('Content-Type: application/x-gzip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        // 允许跨域访问
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET');
        
        // 读取文件并输出
        readfile($filePath);
        exit;
        
    case 'list':
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $backups = $operate->getBackupFiles();
        
        if (!empty($search)) {
            $backups = array_filter($backups, function($backup) use ($search) {
                return stripos($backup['filename'], $search) !== false || stripos($backup['date'], $search) !== false;
            });
        }
        
        echo json_encode(array('success' => true, 'backups' => array_values($backups)));
        break;
        
    case 'backup':
        $result = $operate->backupDatabase();
        if ($result['success']) {
            echo json_encode(array('success' => true, 'filename' => $result['filename']));
        } else {
            echo json_encode(array('success' => false, 'message' => $result['error']));
        }
        break;
        
    case 'restore':
        $filename = isset($_POST['filename']) ? $_POST['filename'] : '';
        if (empty($filename)) {
            echo json_encode(array('success' => false, 'message' => '未指定备份文件'));
            break;
        }
        
        $result = $operate->restoreDatabase($filename);
        if ($result['success']) {
            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('success' => false, 'message' => $result['error']));
        }
        break;
        
    case 'rename':
        $oldName = isset($_POST['oldName']) ? $_POST['oldName'] : '';
        $newName = isset($_POST['newName']) ? $_POST['newName'] : '';
        if (empty($oldName) || empty($newName)) {
            echo json_encode(array('success' => false, 'message' => '旧文件名或新文件名不能为空'));
            break;
        }
        
        if ($operate->renameBackupFile($oldName, $newName)) {
            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('success' => false, 'message' => '重命名备份文件失败'));
        }
        break;
        
    case 'delete':
        $filename = isset($_POST['filename']) ? $_POST['filename'] : '';
        if (empty($filename)) {
            echo json_encode(array('success' => false, 'message' => '未指定要删除的备份文件'));
            break;
        }
        
        if ($operate->deleteBackupFile($filename)) {
            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('success' => false, 'message' => '删除备份文件失败'));
        }
        break;
        
    default:
        echo json_encode(array('success' => false, 'message' => '无效的操作'));
        break;
}
?>