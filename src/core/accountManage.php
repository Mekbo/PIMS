<?php
require_once 'cache.class.php';
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0); // 关闭错误显示，改为自己处理错误

function handleError($message) {
    header('Content-Type: application/json');
    echo json_encode(array('success' => false, 'message' => $message));
    exit;
}

set_error_handler(function($errno, $errstr) {
    handleError($errstr);
});

set_exception_handler(function($e) {
    handleError($e->getMessage());
});

// 设置日志文件
//$logFile = __DIR__ . '/../account_debug.log';
//file_put_contents($logFile, "\n=== 新的账号管理请求 ===\n", FILE_APPEND);
//file_put_contents($logFile, "时间: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
//file_put_contents($logFile, "请求方法: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);
//file_put_contents($logFile, "GET数据: " . print_r($_GET, true) . "\n", FILE_APPEND);
//file_put_contents($logFile, "POST数据: " . print_r($_POST, true) . "\n", FILE_APPEND);

require_once 'conn.php';
require_once 'operate.class.php';

header('Content-Type: application/json');

// 检查用户是否已登录
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => '未登录']);
    exit;
}

$operate = new Operate($pdo);
$action = @$_GET['action'] ? $_GET['action'] : '';

global $cache;
switch ($action) {
    case 'list':
        try {
            $page = intval(@$_GET['page'] ? $_GET['page'] : 1);
            $perPage = intval(@$_GET['perPage'] ? $_GET['perPage'] : 20);
            $search = @$_GET['search'] ? $_GET['search'] : '';

            // 如果是搜索请求，不使用缓存
            if (empty($search)) {
                $cacheKey = "accounts_list_page_{$page}_size_{$perPage}";
                $cachedResult = $cache->get($cacheKey, 'accounts');
                if ($cachedResult !== null) {
                    echo json_encode(array_merge(['success' => true], $cachedResult));
                    exit;
                }
            }

            $result = $operate->getAccounts($page, $perPage, $search);

            // 如果不是搜索请求，缓存结果
            if (empty($search)) {
                $cache->set($cacheKey, $result, 'accounts', 300); // 缓存5分钟
            }
            $response = array('success' => true);
            foreach ($result as $key => $value) {
                $response[$key] = $value;
            }
            $jsonResult = json_encode($response, JSON_UNESCAPED_UNICODE);
            
            if ($jsonResult === false) {
                throw new Exception("JSON编码失败: " . json_last_error_msg());
            }
            echo $jsonResult;
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'message' => '获取账号列表失败：' . $e->getMessage()));
        }
        break;
        
    case 'add':
        try {
           
            $data = array(
                'account_name' => isset($_POST['account_name']) ? $_POST['account_name'] : '',
                'account_password' => isset($_POST['account_password']) ? $_POST['account_password'] : '',
                'nickname' => isset($_POST['nickname']) ? $_POST['nickname'] :'',
                'phone' => isset($_POST['phone']) ? $_POST['phone'] : '',
                'email' => isset($_POST['email']) ? $_POST['email'] : '',
                'security_question' => isset($_POST['security_question']) ? $_POST['security_question'] : '',
                'platform' => isset($_POST['platform']) ? $_POST['platform'] : '',
                'url' => isset($_POST['url']) ? $_POST['url'] : '',
                'register_date' => isset($_POST['register_date']) ? $_POST['register_date'] : date('Y-m-d'),
                'remarks' => isset($_POST['remarks']) ? $_POST['remarks'] : ''
            );
          
            if (empty($data['account_name']) || empty($data['account_password'])) {
                echo json_encode(array('success' => false, 'message' => '账号名和密码不能为空'));
                break;
            }
            
            if ($operate->addAccount($data)) {
                // 清除账号列表缓存和仪表盘缓存
                $cache->clear('accounts');
                $cache->clear('dashboard');
                echo json_encode(array('success' => true));
                echo json_encode(array('success' => true));
            } else {

                echo json_encode(array('success' => false, 'message' => '添加账号失败'));
            }
        } catch (Exception $e) {

            echo json_encode(array('success' => false, 'message' => '添加账号时发生错误：' . $e->getMessage()));
        }
        break;
        
    case 'update':
        $id = intval(@$_GET['account_id'] ? $_GET['account_id'] : 0);
        if ($id <= 0) {		
            echo json_encode(array('success' => false, 'message' => '无效的账号ID'));
            break;
        }
        $data = array(
            'account_name' => isset($_POST['account_name']) ? $_POST['account_name'] : '',
            'account_password' => isset($_POST['account_password']) ? $_POST['account_password'] : '',
            'nickname' => isset($_POST['nickname']) ? $_POST['nickname'] : '',
            'phone' => isset($_POST['phone']) ? $_POST['phone'] : '',
            'email' => isset($_POST['email']) ? $_POST['email'] : '',
            'security_question' => isset($_POST['security_question']) ? $_POST['security_question'] : '',
            'platform' => isset($_POST['platform']) ? $_POST['platform'] : '',
            'url' => isset($_POST['url']) ? $_POST['url'] : '',
            'register_date' => isset($_POST['register_date']) ? $_POST['register_date'] : date('Y-m-d'),
            'remarks' => isset($_POST['remarks']) ? $_POST['remarks'] : ''
        );
        
        if ($operate->updateAccount($id, $data)) {
            // 清除账号列表缓存和仪表盘缓存
            $cache->clear('accounts');
            $cache->clear('dashboard');
            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('success' => false, 'message' => '更新账号失败'));
        }
        break;
        
    case 'get':
        try {           
            $id = intval(isset($_GET['id']) ? $_GET['id'] : 0);
            if ($id <= 0) {               
                echo json_encode(array('success' => false, 'message' => '无效的账号ID'));
                break;
            }            
            $account = $operate->getAccount($id);
            if ($account) {              
                // 记录账号信息（不包括敏感信息）
                $logInfo = "账号信息：\n";
                foreach ($account as $key => $value) {
                    if (!in_array($key, ['account_password', 'phone', 'email', 'security_question'])) {
                        $logInfo .= "$key: " . (is_string($value) ? mb_substr($value, 0, 30) . '...' : $value) . "\n";
                    }
                }
                // 检查每个字段的编码
                foreach ($account as $key => $value) {
                    if (is_string($value)) {
                        $encoding = mb_detect_encoding($value, 'UTF-8, ISO-8859-1', true);
                    }
                }
                
                $jsonResult = json_encode(array('success' => true, 'account' => $account), JSON_UNESCAPED_UNICODE);
                if ($jsonResult === false) {

                } else {

                }
                echo $jsonResult;
            } else {              
                echo json_encode(array('success' => false, 'message' => '获取账号信息失败'));
            }
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'message' => '获取账号信息时发生错误：' . $e->getMessage()));
        }
        break;
        
    case 'delete':
        $id = intval(isset($_GET['id']) ? $_GET['id'] : 0);
        if ($id <= 0) {
            echo json_encode(array('success' => false, 'message' => '无效的账号ID'));
            break;
        }
        
        if ($operate->moveToRecycleBin($id)) {
            // 清除账号列表缓存和仪表盘缓存
            $cache->clear('accounts');
            $cache->clear('dashboard');
            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('success' => false, 'message' => '移动到回收站失败'));
        }
        break;
        
	case 'deleteAll':
        $ids = json_decode(isset($_POST['ids']) ? $_POST['ids'] : '[]', true);
        if (empty($ids)) {
            echo json_encode(array('success' => false, 'message' => '未选择要删除的账号'));			
            break;
        }

        if ($operate->moveToRecycleBin($ids)) {
            // 清除账号列表缓存和仪表盘缓存
            $cache->clear('accounts');
            $cache->clear('dashboard');
            echo json_encode(array('success' => true));			
        } else {
            echo json_encode(array('success' => false, 'message' => '删除账号失败'));			
        }
        break;
		
    case 'export':
        if (!isset($_POST['password']) || !$operate->verifyAdminPassword($_POST['password'])) {
            echo json_encode(array('success' => false, 'message' => '密码验证失败'));
            break;
        }

        $systemType = isset($_POST['systemType']) ? $_POST['systemType'] : 'windows';
        $result = $operate->exportAccounts($systemType);

        if ($result['success']) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
            echo $result['content'];
        } else {
            echo json_encode($result);
        }
        break;

    case 'importAll':
        try {
            if (!isset($_FILES['file'])) {
                throw new Exception('没有接收到文件');
            }

            $file = $_FILES['file'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('文件上传失败');
            }

            // 检查文件扩展名
            $fileInfo = pathinfo($file['name']);
            $extension = strtolower($fileInfo['extension']);
            if ($extension !== 'csv') {
                throw new Exception('请上传CSV文件');
            }

            // 创建临时文件路径
            $tempFile = __DIR__ . '/../temp/' . uniqid('import_', true) . '.txt';
            
            // 读取CSV文件内容并转换为UTF-8编码
            $content = file_get_contents($file['tmp_name']);
            $encoding = mb_detect_encoding($content, array('UTF-8', 'GBK', 'GB2312'));
            if ($encoding !== 'UTF-8') {
                $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            }
            
            // 保存为临时文件
            file_put_contents($tempFile, $content);

            // 调用导入方法1
            $result = $operate->importAllAccounts($tempFile);
            
			//调用导入方法2
			//$result = $operate->_importAllAccounts($tempFile);
            
			// 删除临时文件
            unlink($tempFile);

            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'message' => $e->getMessage()));
        }
        break;

    default:
        echo json_encode(array('success' => false, 'message' => '无效的操作'));
        break;
}
?>