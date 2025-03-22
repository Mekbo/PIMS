<?php
class Operate {
    private $pdo;
    private $encryptionKey = 'PIMS2025#Mekbo'; // 加密密钥
    private $backupPath = DB_BAKUPPATH;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        }
    }

    // 设置备份路径
    public function setBackupPath($path) {
        $this->backupPath = rtrim($path, '/') . '/';
        return true;
    }

    // 获取备份路径
    public function getBackupPath() {
        return $this->backupPath;
    }

    // 加密方法
    public function encrypt($data) {
        if (empty($data)) return '';
        $key = $this->encryptionKey;
        $keyLength = strlen($key);
        $result = '';
        
        // 先进行一次base64编码，确保所有字符都是可打印字符
        $data = base64_encode($data);
        $dataLength = strlen($data);
        
        // 使用密钥进行异或加密
        for($i = 0; $i < $dataLength; $i++) {
            $result .= chr(ord($data[$i]) ^ ord($key[$i % $keyLength]));
        }
        
        // 最后再进行一次base64编码，确保输出是可打印字符
        return base64_encode($result);
    }

    // 解密方法
    public function decrypt($data) {
        if (empty($data)) return '';
        $key = $this->encryptionKey;
        $keyLength = strlen($key);
        $result = '';
        
        // 先解开最外层的base64
        $data = base64_decode($data);
        if ($data === false) return '';
        
        // 使用密钥进行异或解密
        $dataLength = strlen($data);
        for($i = 0; $i < $dataLength; $i++) {
            $result .= chr(ord($data[$i]) ^ ord($key[$i % $keyLength]));
        }
        
        // 最后再解开内层的base64
        $decoded = base64_decode($result);
        return $decoded !== false ? $decoded : '';
    }


    // 添加账号信息
    public function addAccount($data) {
        try {
            // 验证必填字段
            if (empty($data['account_name']) || empty($data['account_password'])) {
                throw new Exception('账号名和密码不能为空');
            }

            $sql = "INSERT INTO tb_accountInfo (account_name, account_password, nickname, phone, email, security_question, platform, url, register_date, remarks) 
                    VALUES (:account_name, :account_password, :nickname, :phone, :email, :security_question, :platform, :url, :register_date, :remarks)";
           
            $stmt = $this->pdo->prepare($sql);
            
            // 绑定并记录参数
            $params = [
                ':account_name' => $data['account_name'],
                ':account_password' => $this->encrypt($data['account_password']),
                ':nickname' => $data['nickname'],
                ':phone' => $this->encrypt($data['phone']),
                ':email' => $this->encrypt($data['email']),
                ':security_question' => $this->encrypt($data['security_question']),
                ':platform' => $data['platform'],
                ':url' => $data['url'],
                ':register_date' => $data['register_date'],
                ':remarks' => $data['remarks']
            ];
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $result = $stmt->execute();
            
            if ($result) {
                $lastId = $this->pdo->lastInsertId();
            } else {
				echo '无效数据!';
            }
            
            return $result;
            
        } catch (PDOException $e) {
            throw new Exception('数据库错误：' . $e->getMessage());
        } catch (Exception $e) {
            throw $e;
        }
    }

    // 更新账号信息
    public function updateAccount($id, $data) {
        try {
            $sql = "UPDATE tb_accountInfo SET 
                    account_name = :account_name, 
                    account_password = :account_password, 
                    nickname = :nickname, 
                    phone = :phone, 
                    email = :email, 
                    security_question = :security_question, 
                    platform = :platform, 
                    url = :url, 
                    register_date = :register_date, 
                    remarks = :remarks 
                    WHERE account_id = :id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id);
            $stmt->bindValue(':account_name', $data['account_name']);
            $stmt->bindValue(':account_password', $this->encrypt($data['account_password']));
            $stmt->bindValue(':nickname', $data['nickname']);
            $stmt->bindValue(':phone', $this->encrypt($data['phone']));
            $stmt->bindValue(':email', $this->encrypt($data['email']));
            $stmt->bindValue(':security_question', $this->encrypt($data['security_question']));
            $stmt->bindValue(':platform', $data['platform']);
            $stmt->bindValue(':url', $data['url']);
            $stmt->bindValue(':register_date', $data['register_date']);
            $stmt->bindValue(':remarks', $data['remarks']);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // 获取单个账号信息
    public function getAccount($id) {
        $logFile = __DIR__ . '/../account_debug.log';
        try {
           
            $stmt = $this->pdo->prepare("SELECT * FROM tb_accountInfo WHERE account_id = :id");
            $stmt->bindValue(':id', $id);
            $stmt->execute();
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $row['account_password'] = $this->decrypt($row['account_password']);
                $row['phone'] = $this->decrypt($row['phone']);
                $row['email'] = $this->decrypt($row['email']);
                $row['security_question'] = $this->decrypt($row['security_question']);
                
                // 确保所有字段都是UTF-8编码
                foreach ($row as $key => $value) {
                    if (is_string($value)) {
                        $row[$key] = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                    }
                }
                
                // 记录解密后的账号信息（不包括敏感信息）
                $logInfo = "解密后的账号信息：\n";
                foreach ($row as $key => $value) {
                    if (!in_array($key, ['account_password', 'phone', 'email', 'security_question'])) {
                        $logInfo .= "$key: $value\n";
                    }
                } 
                return $row;
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }

    // 获取账号列表（带分页和搜索）
    public function getAccounts($page = 1, $perPage = 20, $search = '', $isDeleted = 0) {
        try {
            $offset = ($page - 1) * $perPage;
            
            $sql = "SELECT * FROM tb_accountInfo WHERE is_deleted = :is_deleted";
            $params = array(':is_deleted' => $isDeleted);
            
            if (!empty($search)) {
                $encryptedSearch = $this->encrypt($search);
                $sql .= " AND (account_name LIKE :search OR platform LIKE :search OR url LIKE :search OR phone = :exact_search OR email = :exact_search OR remarks LIKE :search)";
                $params[':search'] = '%' . $search . '%';
            $params[':exact_search'] = $encryptedSearch;
            }


            
            $sql .= " ORDER BY account_id DESC LIMIT :offset, :perPage";
            
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);       
            $stmt->execute();        
            $accounts = [];
			
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                try {
                    // 解密敏感信息
                    $row['account_password'] = $this->decrypt($row['account_password']);
                    $row['phone'] = $this->decrypt($row['phone']);
                    $row['email'] = $this->decrypt($row['email']);
                    $row['security_question'] = $this->decrypt($row['security_question']);
                    
                    // 确保所有字段都是UTF-8编码
                    foreach ($row as $key => $value) {
                        if (is_string($value)) {
                            $row[$key] = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                        }
                    }
                    
                    $accounts[] = $row;
                } catch (Exception $e) {                
                    continue;
                }
            }
            
            // 获取总记录数
            $countSql = "SELECT COUNT(*) FROM tb_accountInfo WHERE is_deleted = :is_deleted";
            $countParams = [':is_deleted' => $isDeleted];
            
            if (!empty($search)) {
                $encryptedSearch = $this->encrypt($search);
                $countSql .= " AND (account_name LIKE :search OR platform LIKE :search OR url LIKE :search OR phone = :exact_search OR email = :exact_search)";
                $countParams[':search'] = "%$search%";
                $countParams[':exact_search'] = $encryptedSearch;
            }
                
            $countStmt = $this->pdo->prepare($countSql);
            foreach ($countParams as $key => $val) {
                $countStmt->bindValue($key, $val);
            }
            $countStmt->execute();
            $totalCount = $countStmt->fetchColumn();
                       
            $result = array(
                'accounts' => $accounts,
                'total' => $totalCount,
                'pages' => ceil($totalCount / $perPage),
                'current' => $page
            );

            return $result;
            
        } catch (PDOException $e) {

            throw new Exception("获取账号列表失败: " . $e->getMessage());
        } catch (Exception $e) {
 
            throw $e;
        }
    }

    // 移动到回收站（单条或多条）
    public function moveToRecycleBin($ids) {
        try {
            if (!is_array($ids)) {
                $ids = [$ids];
            }
            
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "UPDATE tb_accountInfo SET is_deleted = 1 WHERE account_id IN ($placeholders)";
            
            $stmt = $this->pdo->prepare($sql);
            foreach ($ids as $i => $id) {
                $stmt->bindValue($i + 1, $id);
            }
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // 从回收站恢复（单条或多条）
    public function restoreFromRecycleBin($ids) {
        try {
            if (!is_array($ids)) {
                $ids = [$ids];
            }
            
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "UPDATE tb_accountInfo SET is_deleted = 0 WHERE account_id IN ($placeholders)";
            
            $stmt = $this->pdo->prepare($sql);
            foreach ($ids as $i => $id) {
                $stmt->bindValue($i + 1, $id);
            }
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // 彻底删除（单条或多条）
    public function permanentDelete($ids) {
        try {
            if (!is_array($ids)) {
                $ids = [$ids];
            }
            
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "DELETE FROM tb_accountInfo WHERE account_id IN ($placeholders) AND is_deleted = 1";
            
            $stmt = $this->pdo->prepare($sql);
            foreach ($ids as $i => $id) {
                $stmt->bindValue($i + 1, $id);
            }
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // 清空回收站
    public function emptyRecycleBin() {
        try {
            $sql = "DELETE FROM tb_accountInfo WHERE is_deleted = 1";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // 数据库备份 - PHP原生实现
    public function backupDatabase($backupName = '') {
        try {
            if (empty($backupName)) {
                $backupName = 'dbbak_pims_' . date('YmdHis');
            }
            
            $filename = $this->backupPath . $backupName . '.sql';
            
            // 确保备份目录存在
            if (!is_dir($this->backupPath)) {
                if (!mkdir($this->backupPath, 0755, true)) {
                    throw new Exception("无法创建备份目录：" . $this->backupPath);
                }
            }
            
            // 检查备份目录是否可写
            if (!is_writable($this->backupPath)) {
                throw new Exception("备份目录不可写：" . $this->backupPath);
            }
            
            // 获取所有表
            $tables = [];
            $result = $this->pdo->query("SHOW TABLES");
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            
            if (empty($tables)) {
                throw new Exception("数据库中没有找到表");
            }
            
            // 开始备份
            $output = "-- PIMS数据库备份\n";
            $output .= "-- 生成时间: " . date("Y-m-d H:i:s") . "\n";
            $output .= "-- 服务器版本: " . $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
            $output .= "-- PHP 版本: " . phpversion() . "\n\n";
            $output .= "SET FOREIGN_KEY_CHECKS=0;\n";
            $output .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
            $output .= "SET AUTOCOMMIT = 0;\n";
            $output .= "START TRANSACTION;\n";
            $output .= "SET time_zone = \"+08:00\";\n\n";
            
            // 处理每个表
            foreach ($tables as $table) {
                // 表结构
                $output .= "\n-- --------------------------------------------------------\n";
                $output .= "\n-- 表结构 `" . $table . "`\n\n";
                
                $result = $this->pdo->query("SHOW CREATE TABLE `$table`");
                $row = $result->fetch(PDO::FETCH_NUM);
                $output .= $row[1] . ";\n\n";
                
                // 表数据
                $output .= "-- 转存表中的数据 `" . $table . "`\n";
                
                $result = $this->pdo->query("SELECT * FROM `$table`");
                $columnCount = $result->columnCount();
                
                while ($row = $result->fetch(PDO::FETCH_NUM)) {
                    $output .= "INSERT INTO `$table` VALUES (";
                    
                    for ($i = 0; $i < $columnCount; $i++) {
                        if (isset($row[$i])) {
                            $row[$i] = addslashes($row[$i]);
                            $row[$i] = str_replace("\n", "\\n", $row[$i]);
                            $output .= "'" . $row[$i] . "'";
                        } else {
                            $output .= "NULL";
                        }
                        
                        if ($i < ($columnCount - 1)) {
                            $output .= ",";
                        }
                    }
                    
                    $output .= ");\n";
                }
                
                $output .= "\n";
            }
            
            $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
            $output .= "COMMIT;\n";
            
            // 写入文件
            if (file_put_contents($filename, $output) === false) {
                throw new Exception("无法写入备份文件");
            }
            
            // 压缩文件
            $gzFilename = $filename . '.gz';
            $fp = gzopen($gzFilename, 'w9');
            
            if ($fp === false) {
                throw new Exception("无法创建压缩文件");
            }
            
            gzwrite($fp, file_get_contents($filename));
            gzclose($fp);
            
            // 删除原始SQL文件
            unlink($filename);
            
            return array(
                'success' => true,
                'filename' => $backupName . '.sql.gz',
                'path' => $gzFilename
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }


    // 获取备份文件列表
    public function getBackupFiles() {
        $files = [];
        
        if (is_dir($this->backupPath)) {
            $backupFiles = glob($this->backupPath . '*.sql.gz');
            
            foreach ($backupFiles as $file) {
                $filename = basename($file);
                $files[] = array(
                    'filename' => $filename,
                    'path' => $file,
                    'size' => filesize($file),
                    'date' => date('Y-m-d H:i:s', filemtime($file))
                );
            }
        }
        
        return $files;
    }

    // 重命名备份文件
    public function renameBackupFile($oldName, $newName) {
        $oldPath = $this->backupPath . $oldName;
        $newPath = $this->backupPath . $newName;
        
        if (file_exists($oldPath) && !file_exists($newPath)) {
            return rename($oldPath, $newPath);
        }
        
        return false;
    }

    // 删除备份文件
    public function deleteBackupFile($filename) {
        $filePath = $this->backupPath . $filename;
        
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        
        return false;
    }

    // 恢复数据库 - PHP原生实现
    public function restoreDatabase($filename) {
        try {
            $filePath = $this->backupPath . $filename;
            
            if (!file_exists($filePath)) {
                return array(
                    'success' => false,
                    'error' => '备份文件不存在'
                );
            }
            
            // 解压缩备份文件
            $gz = gzopen($filePath, 'r');
            if ($gz === false) {
                return array(
                    'success' => false,
                    'error' => '无法打开压缩文件'
                );
            }
            
            $tempSqlFile = $this->backupPath . 'temp_restore.sql';
            $fp = fopen($tempSqlFile, 'w');
            
            if ($fp === false) {
                gzclose($gz);
                return array(
                    'success' => false,
                    'error' => '无法创建临时SQL文件'
                );
            }
            
            while (!gzeof($gz)) {
                fwrite($fp, gzread($gz, 4096));
            }
            
            gzclose($gz);
            fclose($fp);
            
            // 读取SQL文件并执行
            $sql = file_get_contents($tempSqlFile);
            if ($sql === false) {
                unlink($tempSqlFile);
                return array(
                    'success' => false,
                    'error' => '无法读取SQL文件'
                );
            }
            
            // 分割SQL语句
            $queries = explode(';', $sql);
            
            try {
                $this->pdo->beginTransaction();
                
                // 删除现有的表
                $existingTables = $this->pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($existingTables as $table) {
                    $this->pdo->exec("DROP TABLE IF EXISTS `$table`");
                }
                
                foreach ($queries as $query) {
                    $query = trim($query);
                    if (!empty($query)) {
                        $this->pdo->exec($query);
                    }
                }
                
                $this->pdo->commit();
                unlink($tempSqlFile);
                
                return array(
                    'success' => true
                );
            } catch (PDOException $e) {
                $this->pdo->rollBack();
                unlink($tempSqlFile);
                
                return array(
                    'success' => false,
                    'error' => '恢复数据库失败: ' . $e->getMessage()
                );
            }
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    // 验证管理员密码
    public function verifyAdminPassword($password) {
        try {
            $stmt = $this->pdo->prepare("SELECT password FROM tb_admin WHERE admin_id = 1");
            $stmt->execute();
            $storedPassword = $stmt->fetchColumn();
            
            return md5($password) === $storedPassword;
        } catch (PDOException $e) {
            return false;
        }
    }

    // 更新管理员信息
    public function updateAdminInfo($data) {
        try {
            $sql = "UPDATE tb_admin SET ";
            $params = [];
            
            if (isset($data['nickname'])) {
                $sql .= "nickname = :nickname, ";
                $params[':nickname'] = $data['nickname'];
            }
            
            if (isset($data['avatar'])) {
                $sql .= "avatar = :avatar, ";
                $params[':avatar'] = $data['avatar'];
            }
            
            if (isset($data['password'])) {
                $sql .= "password = :password, ";
                $params[':password'] = md5($data['password']);
            }
            
            // 移除最后的逗号和空格
            $sql = rtrim($sql, ", ");
            
            $sql .= " WHERE admin_id = 1";
            
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // 导出账号信息为CSV
    public function exportAccounts($systemType = 'windows') {
        try {
            // 获取所有未删除的账号
            $stmt = $this->pdo->prepare("SELECT * FROM tb_accountInfo WHERE is_deleted = 0 ORDER BY account_id DESC");
            $stmt->execute();
            
            // 设置CSV文件的表头
            $headers = array(
                '账号名', '密码', '昵称', '手机', '邮箱', 
                '密保问题', '平台', '网址', '注册日期', '备注'
            );
            
            // 创建内存文件句柄
            $output = fopen('php://temp', 'r+');
            
            // 根据系统类型设置编码
            $bom = '';
            if ($systemType === 'windows') {
                $bom = chr(0xEF) . chr(0xBB) . chr(0xBF); // UTF-8 BOM
                fwrite($output, $bom);
            }
            
            // 写入表头
            fputcsv($output, $headers);
            
            // 写入数据
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $csvRow = array(
                    $row['account_name'],
                    $this->decrypt($row['account_password']),
                    $row['nickname'],
                    $this->decrypt($row['phone']),
                    $this->decrypt($row['email']),
                    $this->decrypt($row['security_question']),
                    $row['platform'],
                    $row['url'],
                    $row['register_date'],
                    $row['remarks']
                );
                
                fputcsv($output, $csvRow);
            }
            
            // 获取生成的CSV内容
            rewind($output);
            $csv = stream_get_contents($output);
            fclose($output);
            
            return array(
                'success' => true,
                'content' => $csv,
                'filename' => 'accounts_export_' . date('YmdHis') . '.csv'
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => '导出失败：' . $e->getMessage()
            );
        }
    }

    // 获取管理员信息
    public function getAdminInfo() {
        try {
            $stmt = $this->pdo->prepare("SELECT admin_id, username, nickname, avatar FROM tb_admin WHERE admin_id = 1");
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    // 批量导入账号信息方法1
    public function importAllAccounts($filePath) {

        if (!file_exists($filePath)) {
            throw new Exception('文件不存在');
        }

        $this->pdo->beginTransaction();

        try {
            $file = fopen($filePath,'r');
            $headers = fgetcsv($file);
            $expectedHeaders = ['账号名', '密码', '昵称', '手机', '邮箱', '密保问题', '平台', '网址', '注册日期', '备注'];
            
            // 如果不是预期的标题行，尝试使用英文字段名
            if ($headers !== $expectedHeaders) {
                $expectedHeaders = ['account_name', 'account_password', 'nickname', 'phone', 'email', 'security_question', 'platform', 'url', 'register_date', 'remarks'];
               // if (!array_diff($headers, $expectedHeaders) && array_diff(!$expectedHeaders, $headers)) {
                    // 标题行匹配，继续处理
               // } else {
               //     throw new Exception('CSV文件格式不正确');
               // }
            }

            $successCount = 0;
            $failCount = 0;

            while (($data = fgetcsv($file)) !== FALSE) {
                if (count($data) != count($expectedHeaders)) {
                    $failCount++;
                    continue; // 跳过格式不正确的行
                }             
               
                $accountData = array_combine($expectedHeaders, $data);
                
                // 将中文字段名转换为英文字段名
                if ($headers === ['账号名', '密码', '昵称', '手机', '邮箱', '密保问题', '平台', '网址', '注册日期', '备注']) {
                    $accountData = [
                        'account_name' => $accountData['账号名'],
                        'account_password' => $accountData['密码'],
                        'nickname' => $accountData['昵称'],
                        'phone' => $accountData['手机'],
                        'email' => $accountData['邮箱'],
                        'security_question' => $accountData['密保问题'],
                        'platform' => $accountData['平台'],
                        'url' => $accountData['网址'],
                        'register_date' => $accountData['注册日期'],
                        'remarks' => $accountData['备注']
                    ];
                }
                
                try {
                    if ($this->addAccount($accountData)) {
                        $successCount++;
                    } else {
                        $failCount++;
                    }
                } catch (Exception $e) {
                    $failCount++;
                }
            }

            fclose($file);
            $this->pdo->commit();
            return [
               'success' => true,
               'message' => "导入完成。成功：".$successCount."，失败：".$failCount.""
            ];
        } catch (Exception $e) {
           $this->pdo->rollBack();
		   
           throw $e;
        }
    }
	
	// 批量导入账号信息方法2
    public function _importAllAccounts($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception('文件不存在');
        }

        $this->pdo->beginTransaction();

        try {
            // 以UTF-8编码打开文件
            $file = fopen($filePath, 'r');
            if (!$file) {
                throw new Exception('无法打开文件');
            }

            $successCount = 0;
            $failCount = 0;
            $isFirstLine = true;

            // 逐行读取CSV文件
            while (($data = fgetcsv($file)) !== FALSE) {
                // 跳过第一行（表头）
                if ($isFirstLine) {
                    $isFirstLine = false;
                    continue;
                }

                // 确保每行都有10个字段
                if (count($data) != 10) {
                    $failCount++;
                    continue;
                }

                // 处理编码问题
                $data = array_map(function($value) {
                    // 检测编码并转换为UTF-8
                    $encoding = mb_detect_encoding($value, ['ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5']);
                    return $encoding ? mb_convert_encoding($value, 'UTF-8', $encoding) : $value;
                }, $data);
			 				 
                // 构建账号数据数组
                $accountData = [
                    'account_name' => $data[0],
                    'account_password' => $data[1],
                    'nickname' => $data[2],
                    'phone' => $data[3],
                    'email' => $data[4],
                    'security_question' => $data[5],
                    'platform' => $data[6],
                    'url' => $data[7],
                    'register_date' => $data[8] ?: date('Y-m-d'), // 如果为空则使用当前日期
                    'remarks' => $data[9]
                ];

                try {
                    if ($this->addAccount($accountData)) {
                        $successCount++;
                    } else {
                        $failCount++;
                    }
                } catch (Exception $e) {
                    $failCount++;
                }
            }

            fclose($file);
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => "导入完成。成功：" .$successCount. "，失败：" .$failCount.""
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
			
}
?>