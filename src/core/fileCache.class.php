<?php
/**
 * 文件缓存实现类
 * Created: 2025-03-21
 */
class FileCache implements CacheInterface {
    private $cachePath;     // 缓存根目录
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
     */
    public function __construct($config = []) {
        $this->cachePath = isset($config['cachePath']) ? $config['cachePath'] : '../temp/cache';
        $this->cacheTime = isset($config['cacheTime']) ? $config['cacheTime'] : 300;
        $this->cacheKey = 'PIMS_CACHE_' . date('Ymd'); // 每天更新的缓存密钥
        $this->initCacheDir();
    }

    /**
     * 初始化缓存目录
     */
    private function initCacheDir() {
        $dirs = ['accounts', 'system', 'dashboard'];
        foreach ($dirs as $dir) {
            $path = $this->cachePath . '/' . $dir;
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
    }

    /**
     * 生成缓存键
     * @param string $key 原始键名
     * @return string
     */
    private function generateKey($key) {
        return md5($key);
    }

    /**
     * 获取缓存文件路径
     * @param string $key 缓存键名
     * @param string $category 缓存类别
     * @return string
     */
    private function getCacheFile($key, $category = 'accounts') {
        return $this->cachePath . '/' . $category . '/' . $this->generateKey($key) . '.cache';
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
        
        $cacheFile = $this->getCacheFile($key, $category);
        $cacheData = [
            'data' => $this->cacheEncrypt($data),
            'expire' => time() + $expire
        ];
        
        // 使用文件锁确保并发写入安全
        $fp = fopen($cacheFile, 'w');
        if ($fp === false) return false;
        
        if (flock($fp, LOCK_EX)) {
            $result = fwrite($fp, serialize($cacheData));
            flock($fp, LOCK_UN);
        } else {
            $result = false;
        }
        fclose($fp);
        
        return $result !== false;
    }

    /**
     * 读取缓存
     * @param string $key 缓存键名
     * @param string $category 缓存类别
     * @return mixed|null
     */
    public function get($key, $category = 'accounts') {
        $cacheFile = $this->getCacheFile($key, $category);
        
        if (!file_exists($cacheFile)) {
            return null;
        }
        
        $fp = fopen($cacheFile, 'r');
        if ($fp === false) return null;
        
        if (flock($fp, LOCK_SH)) {
            $data = fread($fp, filesize($cacheFile));
            flock($fp, LOCK_UN);
        } else {
            fclose($fp);
            return null;
        }
        fclose($fp);
        
        $cacheData = @unserialize($data);
        if ($cacheData === false) {
            return null;
        }
        
        // 检查是否过期
        if (time() > $cacheData['expire']) {
            @unlink($cacheFile);
            return null;
        }
        
        return $this->cacheDecrypt($cacheData['data']);
    }

    /**
     * 删除缓存
     * @param string $key 缓存键名
     * @param string $category 缓存类别
     * @return bool
     */
    public function delete($key, $category = 'accounts') {
        $cacheFile = $this->getCacheFile($key, $category);
        if (file_exists($cacheFile)) {
            return @unlink($cacheFile);
        }
        return true;
    }

    /**
     * 清空指定类别的缓存
     * @param string $category 缓存类别
     * @return bool
     */
    public function clear($category = 'accounts') {
        $path = $this->cachePath . '/' . $category;
        if (!is_dir($path)) {
            return true;
        }
        
        $files = glob($path . '/*.cache');
        foreach ($files as $file) {
            @unlink($file);
        }
        
        return true;
    }

    /**
     * 清理过期缓存
     * @return int 清理的数量
     */
    public function gc() {
        $count = 0;
        $dirs = ['accounts', 'system', 'dashboard'];
        
        foreach ($dirs as $category) {
            $path = $this->cachePath . '/' . $category;
            if (!is_dir($path)) continue;
            
            $files = glob($path . '/*.cache');
            foreach ($files as $file) {
                $fp = fopen($file, 'r');
                if ($fp === false) continue;
                
                if (flock($fp, LOCK_SH)) {
                    $data = fread($fp, filesize($file));
                    flock($fp, LOCK_UN);
                    fclose($fp);
                    
                    $cacheData = @unserialize($data);
                    if ($cacheData !== false && time() > $cacheData['expire']) {
                        if (@unlink($file)) {
                            $count++;
                        }
                    }
                } else {
                    fclose($fp);
                }
            }
        }
        
        return $count;
    }
}