<?php
header('Content-Type: text/html; charset=UTF-8');

/**
 * 页面跳转
 */
function redirect($url) {
    header('Content-Type: text/html; charset=UTF-8');
    header("Location: {$url}");
    exit;
}

/**
 * 检查PHP版本
 */
function checkPHPVersion() {
    return version_compare(PHP_VERSION, '5.3.0', '>=');
}

/**
 * 检查必要的PHP扩展
 */
function checkExtensions() {
    $required = ['pdo_mysql', 'json', 'session'];
    $missing = [];
    
    foreach ($required as $ext) {
        if (!extension_loaded($ext)) {
            $missing[] = $ext;
        }
    }
    
    return $missing;
}

/**
 * 检查目录权限
 */
/**
 * 检查目录权限
 * 兼容 Windows 和 Linux 系统
 */
function checkDirectories() {
    // 获取项目根目录的绝对路径，并规范化路径分隔符
    $base_path = rtrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, dirname(dirname(dirname(__FILE__)))), DIRECTORY_SEPARATOR);
    
    $dirs = [
        'core',
        'backups',
        'temp/cache'
    ];
    
    $results = [];
    foreach ($dirs as $dir) {
        $display_path = '../' . $dir;
        // 规范化目录路径，确保使用系统对应的目录分隔符
        $normalized_dir = rtrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $dir), DIRECTORY_SEPARATOR);
        $check_path = $base_path . DIRECTORY_SEPARATOR . $normalized_dir;
        
        // 检查目录是否存在
        $exists = is_dir($check_path);
        
        // 使用实际写入测试来验证权限
        $is_writable = false;
        if ($exists) {
            // 先检查PHP进程对目录的写入权限
            if (@is_writable($check_path)) {
                // 尝试写入测试文件
                $test_file = $check_path . DIRECTORY_SEPARATOR . '.write_test_' . time() . '.tmp';
                $test_result = @file_put_contents($test_file, 'test');
                if ($test_result !== false) {
                    $is_writable = true;
                    @unlink($test_file);  // 删除测试文件
                }
            }
        }
        
        $results[$display_path] = [
            'exists' => $exists,
            'writable' => $is_writable,
            'message' => !$exists ? '目录不存在' : ($is_writable ? '可写' : '不可写')
        ];
    }
    
    return $results;
}

/**
 * 获取推荐的配置值
 */
function getRecommendedConfig() {
    return [
        'host' => 'localhost',
        'port' => '3306',
        'username' => 'root',
        'password' => '',
        'database' => 'db_pims',
        'charset' => 'utf8mb4',
        'backup_path' => '../backups/'
    ];
}