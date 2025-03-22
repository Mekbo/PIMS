<?php
header('Content-Type: text/html; charset=UTF-8');
$recommended = getRecommendedConfig();
?>
<form method="post" action="index.php?step=2" id="configForm">
    <h2>系统配置</h2>
    
    <div class="config-section">
        <h3>数据库配置</h3>
        
        <div class="form-group">
            <label for="host">数据库主机：</label>
            <input type="text" id="host" name="host" value="<?php echo $recommended['host']; ?>" required>
            <span class="help">数据库服务器地址，一般为localhost</span>
        </div>
        
        <div class="form-group">
            <label for="port">端口：</label>
            <input type="number" id="port" name="port" value="<?php echo $recommended['port']; ?>" required>
            <span class="help">数据库端口，一般为3306</span>
        </div>
        
        <div class="form-group">
            <label for="username">用户名：</label>
            <input type="text" id="username" name="username" value="<?php echo $recommended['username']; ?>" required>
            <span class="help">数据库用户名</span>
        </div>
        
        <div class="form-group">
            <label for="password">密码：</label>
            <input type="password" id="password" name="password" value="<?php echo $recommended['password']; ?>">
            <span class="help">数据库密码</span>
        </div>
        
        <div class="form-group">
            <label for="database">数据库名：</label>
            <input type="text" id="database" name="database" value="<?php echo $recommended['database']; ?>" required>
            <span class="help">数据库名称</span>
        </div>
        
        <div class="form-group">
            <label for="charset">字符集：</label>
            <input type="text" id="charset" name="charset" value="<?php echo $recommended['charset']; ?>" required>
            <span class="help">数据库字符集，建议使用utf8mb4</span>
        </div>
    </div>
    
    <div class="config-section">
        <h3>系统路径配置</h3>
        
        <div class="form-group">
            <label for="backup_path">备份路径：</label>
            <input type="text" id="backup_path" name="backup_path" value="<?php echo $recommended['backup_path']; ?>" required>
            <span class="help">数据库备份文件存储路径，相对于系统根目录</span>
        </div>
    </div>
    
    <div class="actions">
        <button type="button" class="button" onclick="testConnection()">测试连接</button>
        <a href="index.php?step=1" class="button prev">上一步</a>
        <button type="submit" class="button next">下一步</button>
    </div>
</form>