<?php
// 调试信息
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 加载配置文件
$config = require_once __DIR__ . '/config.php';

// 检查 Memcache 扩展
if (class_exists('Memcache')) {
    error_log('Memcache extension is installed.');
} else {
    error_log('Memcache extension is NOT installed.');
}

// 缓存配置
define('CACHE_TYPE', 'file');    // 可选值：memcache|file
define('CACHE_HOST', '127.0.0.1');   // Memcache服务器地址
define('CACHE_PORT', '11211');       // Memcache服务器端口
define('CACHE_PREFIX', 'PIMS_');     // 缓存键前缀

// 设置缓存类型
putenv('PIMS_CACHE_TYPE=' . CACHE_TYPE);

require_once 'cache.class.php';

// 如果使用Memcache但连接失败，会自动降级到文件缓存，这里添加错误处理
try {
    error_log('Attempting to initialize cache...');
    if (CACHE_TYPE === 'memcache') {
        if (!class_exists('Memcache')) {
            error_log('Memcache extension not installed, falling back to file cache');
            putenv('PIMS_CACHE_TYPE=file');
        } else {
            // 测试Memcache连接
            $memcache = new Memcache();
            try {
                if (!$memcache->connect(CACHE_HOST, CACHE_PORT, 1)) {
                    error_log('Cannot connect to Memcache server at ' . CACHE_HOST . ':' . CACHE_PORT . ', falling back to file cache');
                    putenv('PIMS_CACHE_TYPE=file');
                } else {
                    error_log('Successfully connected to Memcache server at ' . CACHE_HOST . ':' . CACHE_PORT);
                    $memcache->close();
                }
            } catch (Exception $e) {
                error_log('Memcache connection error: ' . $e->getMessage() . ', falling back to file cache');
                putenv('PIMS_CACHE_TYPE=file');
            }
        }
    }
    
    // 初始化缓存对象
    $cache = new Cache();
    error_log('Cache initialized successfully.');
} catch (Exception $e) {
    error_log('Cache initialization error: ' . $e->getMessage());
    putenv('PIMS_CACHE_TYPE=file');
}

// 定期清理过期缓存（1%的概率执行，避免频繁清理）
if (rand(1, 100) === 1) {
    $cache->gc();
}

// 输出当前使用的缓存类型到日志（便于调试）
$actualCacheType = getenv('PIMS_CACHE_TYPE') ?: 'file';
error_log('PIMS using cache type: ' . $actualCacheType);

@date_default_timezone_set('Asia/Shanghai');

// 从配置文件获取数据库连接信息
define('DB_HOST', $config['DB_HOST']);
define('DB_PORT', $config['DB_PORT']);
define('DB_NAME', $config['DB_DATABASE']);
define('DB_USER', $config['DB_USERNAME']);
define('DB_PASS', $config['DB_PASSWORD']);
define('DB_BAKUPPATH', $config['BACKUP_PATH']); // 数据库备份位置

try {
    $dsn = "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=".$config['DB_CHARSET'];
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES ".$config['DB_CHARSET']);
} catch(PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}
?>