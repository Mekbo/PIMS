<?php
/**
 * 缓存接口类
 * Created: 2025-03-21
 */
interface CacheInterface {
    /**
     * 写入缓存
     * @param string $key 缓存键名
     * @param mixed $data 缓存数据
     * @param string $category 缓存类别
     * @param int $expire 过期时间（秒）
     * @return bool
     */
    public function set($key, $data, $category = 'accounts', $expire = null);

    /**
     * 读取缓存
     * @param string $key 缓存键名
     * @param string $category 缓存类别
     * @return mixed|null
     */
    public function get($key, $category = 'accounts');

    /**
     * 删除缓存
     * @param string $key 缓存键名
     * @param string $category 缓存类别
     * @return bool
     */
    public function delete($key, $category = 'accounts');

    /**
     * 清空指定类别的缓存
     * @param string $category 缓存类别
     * @return bool
     */
    public function clear($category = 'accounts');

    /**
     * 清理过期缓存
     * @return int 清理的数量
     */
    public function gc();
}