<?php
/**
 * 缓存工厂类
 * Created: 2025-03-21
 */
class CacheFactory {
    /**
     * 获取缓存实例
     * @param string $type 缓存类型：file|memcache|null(自动检测)
     * @param array $config 配置信息
     * @return CacheInterface
     */
    public static function getCache($type = null, $config = []) {
        if ($type === null) {
            // 自动检测最优缓存方式
            if (class_exists('Memcache') && self::testMemcache()) {
                $type = 'memcache';
            } else {
                $type = 'file';
            }
        }
        
        switch ($type) {
            case 'memcache':
                require_once 'memCache.class.php';
                return new PIMemCache($config);
            default:
                require_once 'fileCache.class.php';
                return new FileCache($config);
        }
    }

    /**
     * 测试Memcache连接
     * @return bool
     */
    private static function testMemcache() {
        try {
            $memcache = new Memcache();
            // 尝试连接本地Memcache服务
            if (@$memcache->connect('127.0.0.1', 11211, 1)) {
                $memcache->close();
                return true;
            }
        } catch (Exception $e) {
            // 连接失败，记录日志
            error_log("Memcache connection test failed: " . $e->getMessage());
        }
        return false;
    }
}