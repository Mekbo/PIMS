<?php
header('Content-Type: text/html; charset=UTF-8');

class Installer {
    private $config = [];
    private $error = '';
    
    /**
     * 保存配置信息
     */
    public function saveConfig($data) {
        // 验证必填项
        $required = ['host', 'port', 'username', 'database', 'charset'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['status' => false, 'message' => "请填写 {$field}"];
            }
        }
        
        // 验证端口号
        if (!is_numeric($data['port']) || $data['port'] < 1 || $data['port'] > 65535) {
            return ['status' => false, 'message' => '端口号必须是1-65535之间的数字'];
        }
        
        // 测试数据库连接
        try {
            $dsn = "mysql:host={$data['host']};port={$data['port']}";
            $pdo = new PDO($dsn, $data['username'], $data['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            return ['status' => false, 'message' => '数据库连接失败：' . $e->getMessage()];
        }
        
        // 检查数据库是否存在，不存在则创建
        try {
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$data['database']}` CHARACTER SET {$data['charset']}");
        } catch (PDOException $e) {
            return ['status' => false, 'message' => '创建数据库失败：' . $e->getMessage()];
        }
        
        // 验证备份目录
        $backup_path = $data['backup_path'] ? $data['backup_path'] : '../backups/';
        $real_backup_path = realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR . trim($backup_path, '/\\') . DIRECTORY_SEPARATOR;
        
        if (!file_exists($real_backup_path)) {
            if (!mkdir($real_backup_path, 0755, true)) {
                return ['status' => false, 'message' => '无法创建备份目录'];
            }
        } elseif (!is_writable($real_backup_path)) {
            return ['status' => false, 'message' => '备份目录没有写入权限'];
        }
        
        // 生成配置文件内容
        $config = [
            'DB_HOST' => $data['host'],
            'DB_PORT' => $data['port'],
            'DB_USERNAME' => $data['username'],
            'DB_PASSWORD' => $data['password'],
            'DB_DATABASE' => $data['database'],
            'DB_CHARSET' => $data['charset'],
            'BACKUP_PATH' => $backup_path
        ];
        
        // 保存配置到会话
        $_SESSION['install_config'] = $config;
        
        // 生成配置文件
        $config_content = $this->generateConfigFile($config);
        $config_file = dirname(__DIR__) . '/core/config.php';
        
        if (file_put_contents($config_file, $config_content) === false) {
            return ['status' => false, 'message' => '无法写入配置文件'];
        }
        
        return ['status' => true];
    }
    
    /**
     * 生成配置文件内容
     */
    private function generateConfigFile($config) {
        $template = file_get_contents(__DIR__ . '/config_tpl.php');
        
        $replacements = [
            '{DB_HOST}' => $config['DB_HOST'],
            '{DB_PORT}' => $config['DB_PORT'],
            '{DB_USERNAME}' => $config['DB_USERNAME'],
            '{DB_PASSWORD}' => $config['DB_PASSWORD'],
            '{DB_DATABASE}' => $config['DB_DATABASE'],
            '{DB_CHARSET}' => $config['DB_CHARSET'],
            '{BACKUP_PATH}' => $config['BACKUP_PATH']
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
    
    /**
     * 导入数据库
     */
    public function importDatabase() {
        if (!isset($_SESSION['install_config'])) {
            return ['status' => false, 'message' => '配置信息不存在，请返回上一步'];
        }
        
        $config = $_SESSION['install_config'];
        
        try {
            // 连接数据库
            $dsn = "mysql:host={$config['DB_HOST']};port={$config['DB_PORT']};dbname={$config['DB_DATABASE']}";
            $pdo = new PDO($dsn, $config['DB_USERNAME'], $config['DB_PASSWORD']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec("SET NAMES {$config['DB_CHARSET']}");
            
            // 读取SQL文件
            $sql_file = __DIR__ . '/data/db_pims_init.sql';
            if (!file_exists($sql_file)) {
                return ['status' => false, 'message' => '数据库初始化文件不存在：' . $sql_file];
            }
            
            $sql = file_get_contents($sql_file);
            
            // 执行SQL语句
            $pdo->exec($sql);
            
            // 创建安装锁定文件
            file_put_contents(__DIR__ . '/install.lock', date('Y-m-d H:i:s'));
            
            return ['status' => true, 'message' => '安装成功！系统已完成所有配置。'];
            
        } catch (PDOException $e) {
            return ['status' => false, 'message' => '数据库导入失败：' . $e->getMessage()];
        } catch (Exception $e) {
            return ['status' => false, 'message' => '系统错误：' . $e->getMessage()];
        }
    }
}