<?php 
header('Content-Type: text/html; charset=UTF-8');

// 检查是否已完成安装
$isInstalled = file_exists(dirname(__DIR__) . '/install.lock');
if (!$isInstalled) {
    echo '<div class="error-message">请先完成安装流程</div>';
    echo '<div class="actions"><a href="index.php?step=1" class="button prev">返回安装</a></div>';
    exit;
}

// 获取配置信息
$configFile = dirname(dirname(__DIR__)) . '/core/config.php';
$configExists = file_exists($configFile);
?>
<div class="install-complete">
    <h2>安装完成</h2>
    
    <div class="success-message">
        <div class="icon">✓</div>
        <p>恭喜您！PIMS系统已成功安装。</p>
    </div>
    
    <div class="account-info">
        <h3>管理员账号信息</h3>
        <p>用户名：admin</p>
        <p>密码：123456</p>
        <p class="warning">请登录后立即修改默认密码！</p>
    </div>
    
    <div class="config-info">
        <h3>配置文件位置</h3>
        <p>配置文件路径：/core/config.php</p>
        <?php if ($configExists): ?>
        <p>配置文件已成功生成。如需修改配置，请编辑此文件。</p>
        <?php else: ?>
        <p class="warning">警告：配置文件未找到。请检查安装过程是否正确完成。</p>
        <?php endif; ?>
    </div>
    
    <div class="actions">
        <a href="../../index.php" class="button next">进入系统</a>
    </div>
</div>

<script>
// 确保页面完全加载
document.addEventListener('DOMContentLoaded', function() {
    console.log('安装完成页面已加载');
});
</script>