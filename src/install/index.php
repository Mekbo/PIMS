<?php
header('Content-Type: text/html; charset=UTF-8');
// 安装程序入口文件
session_start();

// 设置错误处理
error_reporting(E_ALL);
ini_set('display_errors', 0);
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['status' => false, 'message' => $errstr]);
        exit;
    }
    return false;
});

// 检查是否已安装
if (file_exists(__DIR__ . '/install.lock') && !isset($_GET['force'])) {
    // 获取配置信息
    $configFile = dirname(__DIR__) . '/core/config.php';
    $configExists = file_exists($configFile);
    
    echo '<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>安装提示</title>
    <style>
        body {
            font-family: "Microsoft YaHei", sans-serif;
            margin: 50px auto;
            max-width: 800px;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .message {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .warning {
            color: #e74c3c;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .info {
            color: #34495e;
            line-height: 1.6;
        }
        .success-message {
            margin: 20px 0;
            color: #27ae60;
            font-weight: bold;
        }
        .account-info {
            background-color: #f9f9f9;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            border-left: 4px solid #3498db;
            text-align: left;
        }
        .config-info {
            background-color: #f9f9f9;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            border-left: 4px solid #2ecc71;
            text-align: left;
        }
        .actions {
            margin-top: 20px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 3px;
            transition: background-color 0.3s;
            margin: 0 10px;
        }
        .button:hover {
            background-color: #2980b9;
        }
        .button.green {
            background-color: #2ecc71;
        }
        .button.green:hover {
            background-color: #27ae60;
        }
    </style>
</head>
<body>
    <div class="message">
        <div class="warning">系统已经安装</div>
        
        <div class="success-message">
            <div>✓ PIMS系统已成功安装</div>
        </div>
        
        <div class="account-info">
            <h3>管理员账号信息</h3>
            <p>用户名：admin</p>
            <p>密码：123456</p>
            <p class="warning" style="font-size: 14px; margin: 0;">请登录后立即修改默认密码！</p>
        </div>
        
        <div class="config-info">
            <h3>配置文件位置</h3>
            <p>配置文件路径：/core/config.php</p>
            <?php if ($configExists): ?>
            <p>配置文件已成功生成。如需修改配置，请编辑此文件。</p>
            <?php else: ?>
            <p class="warning" style="font-size: 14px; margin: 0;">警告：配置文件未找到。请检查安装过程是否正确完成。</p>
            <?php endif; ?>
        </div>
        
        <div class="actions">
            <a href="../index.php" class="button green">进入系统</a>
            <a href="?force=1" class="button">重新安装</a>
        </div>
        
        <div class="info" style="margin-top: 20px; font-size: 14px;">
            如需重新安装，您也可以删除 install/install.lock 文件后刷新页面
        </div>
		 <p style="font-size:0.8em">2025&copy;Mekbo Team Powered by Arlvin (PIMS v1.0）</p>
    </div>
</body>
</html>';
    exit;
}

// 定义安装步骤
$steps = [
    1 => '环境检测',
    2 => '系统配置',
    3 => '数据导入',
    4 => '安装完成'
];

// 获取当前步骤
$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
if ($step < 1 || $step > count($steps)) {
    $step = 1;
}

// 页面标题
$title = 'PIMS系统安装向导 - ' . $steps[$step];

// 引入公共函数
require_once __DIR__ . '/inc/function.php';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_once __DIR__ . '/install.php';
        $installer = new Installer();
        
        // 检查是否是Ajax请求
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        switch ($step) {
            case 2: // 处理系统配置
                $result = $installer->saveConfig($_POST);
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode($result);
                    exit;
                } else if ($result['status']) {
                    redirect('index.php?step=3');
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 3: // 处理数据导入
                $result = $installer->importDatabase();
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode($result);
                    exit;
                } else if ($result['status']) {
                    redirect('index.php?step=4');
                } else {
                    $error = $result['message'];
                }
                break;
        }
    } catch (Exception $e) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            exit;
        }
        $error = $e->getMessage();
    }
}

// 获取步骤内容
$content_file = __DIR__ . '/inc/step_' . $step . '.php';

// 如果是最后一步且是通过正常流程到达的
if ($step === 4 && isset($_SESSION['install_config'])) {
    $config = $_SESSION['install_config'];
}
?>
<?php header('Content-Type: text/html; charset=UTF-8'); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="css/install.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>PIMS系统安装向导</h1>
            <div class="steps">
                <?php foreach ($steps as $key => $name): ?>
                <div class="step <?php echo $key == $step ? 'active' : ($key < $step ? 'done' : ''); ?>">
                    <div class="step-number"><?php echo $key; ?></div>
                    <div class="step-name"><?php echo $name; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </header>
        
        <main>
            <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (file_exists($content_file)): ?>
                <?php include $content_file; ?>
            <?php else: ?>
                <div class="error-message">步骤文件不存在</div>
            <?php endif; ?>
        </main>
        
        <footer>
            <p><?php echo date('Y'); ?>&copy;Mekbo Team Powered by Arlvin （PIMS v1.0）</p>
        </footer>
    </div>
    
    <script src="js/install.js"></script>
</body>
</html>