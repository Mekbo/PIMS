<?php
/**
 * 缓存包装类 - 用于向后兼容
 * Created: 2025-03-21
 */
require_once 'cacheInterface.class.php';
require_once 'cacheFactory.class.php';

class Cache {
    private $cacheInstance;

    public function __construct($cachePath = '../temp/cache', $cacheTime = 300) {
        // 从配置文件或环境变量获取缓存类型
        $cacheType = getenv('PIMS_CACHE_TYPE'); // 可以在环境变量中设置
        
        $config = [
            'cachePath' => $cachePath,
            'cacheTime' => $cacheTime,
            'host' => '127.0.0.1',  // Memcache配置
            'port' => 11211,
            'prefix' => 'PIMS_'
        ];

        try {
            $this->cacheInstance = CacheFactory::getCache($cacheType, $config);
        } catch (Exception $e) {
            // 如果Memcache初始化失败，回退到文件缓存
            error_log("Cache initialization failed: " . $e->getMessage());
            $this->cacheInstance = CacheFactory::getCache('file', $config);
        }
    }

    public function set($key, $data, $category = 'accounts', $expire = null) {
        return $this->cacheInstance->set($key, $data, $category, $expire);
    }

    public function get($key, $category = 'accounts') {
        return $this->cacheInstance->get($key, $category);
    }

    public function delete($key, $category = 'accounts') {
        return $this->cacheInstance->delete($key, $category);
    }

    public function clear($category = 'accounts') {
        return $this->cacheInstance->clear($category);
    }

    public function gc() {
        return $this->cacheInstance->gc();
    }
}