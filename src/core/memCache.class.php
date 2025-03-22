<?php
/**
 * Memcache缓存实现类
 * Created: 2025-03-21
 */
class PIMemCache implements CacheInterface {
    private $memcache;      // Memcache实例
    private $prefix;        // 键名前缀，用于多应用共享Memcache时区分
    private $cacheTime;     // 默认缓存时间
    private $cacheKey;      // 缓存加密密钥

    // 需要加密的敏感字段
    private $sensitiveFields = [
        'account_password',
        'phone',
        'email',
        'security_question',
        'password'  // 管理员密码
    ];

    /**
     * 构造函数
     * @param array $config 配置信息
     * @throws Exception
     */
    public function __construct($config = []) {
        if (!class_exists('Memcache')) {
            throw new Exception('Memcache extension not installed');
        }

        $this->memcache = new Memcache();
        $host = isset($config['host']) ? $config['host'] : '127.0.0.1';
        $port = isset($config['port']) ? $config['port'] : 11211;
        
        if (!$this->memcache->connect($host, $port)) {
            throw new Exception('Cannot connect to Memcache server');
        }

        $this->prefix = isset($config['prefix']) ? $config['prefix'] : 'PIMS_';
        $this->cacheTime = isset($config['cacheTime']) ? $config['cacheTime'] : 300;
        $this->cacheKey = 'PIMS_CACHE_' . date('Ymd'); // 每天更新的缓存密钥
    }

    /**
     * 生成带前缀的缓存键
     * @param string $key 原始键名
     * @param string $category 缓存类别
     * @return string
     */
    private function generateKey($key, $category) {
        return $this->prefix . $category . '_' . md5($key);
    }

    /**
     * 加密敏感数据
     * @param mixed $data 要加密的数据
     * @return mixed
     */
    private function cacheEncrypt($data) {
        if (empty($data)) return $data;
        
        // 如果是数组，递归处理敏感字段
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (in_array($key, $this->sensitiveFields)) {
                    $data[$key] = $this->cacheEncrypt($value);
                } else if (is_array($value)) {
                    $data[$key] = $this->cacheEncrypt($value);
                }
            }
            return $data;
        }
        
        // 字符串加密
        $data = (string)$data;
        $key = md5($this->cacheKey);
        $x = 0;
        $len = strlen($data);
        $l = strlen($key);
        $char = $str = '';
        
        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) $x = 0;
            $char .= $key[$x];
            $x++;
        }
        
        for ($i = 0; $i < $len; $i++) {
            $str .= chr(ord($data[$i]) + (ord($char[$i]) % 256));
        }
        
        return base64_encode($str);
    }

    /**
     * 解密敏感数据
     * @param mixed $data 要解密的数据
     * @return mixed
     */
    private function cacheDecrypt($data) {
        if (empty($data)) return $data;
        
        // 如果是数组，递归处理敏感字段
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (in_array($key, $this->sensitiveFields)) {
                    $data[$key] = $this->cacheDecrypt($value);
                } else if (is_array($value)) {
                    $data[$key] = $this->cacheDecrypt($value);
                }
            }
            return $data;
        }
        
        // 字符串解密
        $data = (string)$data;
        $key = md5($this->cacheKey);
        $x = 0;
        $data = base64_decode($data);
        $len = strlen($data);
        $l = strlen($key);
        $char = $str = '';
        
        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) $x = 0;
            $char .= substr($key, $x, 1);
            $x++;
        }
        
        for ($i = 0; $i < $len; $i++) {
            $str .= chr((ord($data[$i]) - (ord($char[$i]) % 256)));
        }
        
        return $str;
    }

    /**
     * 写入缓存
     * @param string $key 缓存键名
     * @param mixed $data 缓存数据
     * @param string $category 缓存类别
     * @param int $expire 过期时间（秒）
     * @return bool
     */
    public function set($key, $data, $category = 'accounts', $expire = null) {
        if ($expire === null) {
            $expire = $this->cacheTime;
        }
        
        $cacheKey = $this->generateKey($key, $category);
        $cacheData = $this->cacheEncrypt($data);
        
        // 检查常量是否已定义
        $flag = defined('MEMCACHE_COMPRESSED') ? MEMCACHE_COMPRESSED : 0;
        
        return $this->memcache->set($cacheKey, $cacheData, $flag, $expire);
    }

    /**
     * 读取缓存
     * @param string $key 缓存键名
     * @param string $category 缓存类别
     * @return mixed|null
     */
    public function get($key, $category = 'accounts') {
        $cacheKey = $this->generateKey($key, $category);
        $data = $this->memcache->get($cacheKey);
        
        if ($data === false) {
            return null;
        }
        
        return $this->cacheDecrypt($data);
    }

    /**
     * 删除缓存
     * @param string $key 缓存键名
     * @param string $category 缓存类别
     * @return bool
     */
    public function delete($key, $category = 'accounts') {
        $cacheKey = $this->generateKey($key, $category);
        return $this->memcache->delete($cacheKey);
    }

    /**
     * 清空指定类别的缓存
     * @param string $category 缓存类别
     * @return bool
     */
    public function clear($category = 'accounts') {
        // Memcache不支持按前缀删除，这里采用增加版本号的方式使旧缓存失效
        $this->prefix = $this->prefix . time() . '_';
        return true;
    }

    /**
     * 清理过期缓存
     * @return int
     */
    public function gc() {
        // Memcache自动处理过期缓存，无需手动清理
        return 0;
    }

    /**
     * 析构函数
     */
    public function __destruct() {
        if ($this->memcache) {
            $this->memcache->close();
        }
    }
}