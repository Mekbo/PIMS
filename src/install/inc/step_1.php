<?php
header('Content-Type: text/html; charset=UTF-8');
$php_version = checkPHPVersion();
$extensions = checkExtensions();
$directories = checkDirectories();

// 确保所有目录都存在且可写
$all_dirs_ok = true;
foreach ($directories as $dir) {
    if (!$dir['exists'] || !$dir['writable']) {
        $all_dirs_ok = false;
        break;
    }
}

$can_install = $php_version && empty($extensions) && $all_dirs_ok;
?>
<div class="check-results">
    <h2>环境检测</h2>
    
    <div class="check-item">
        <h3>PHP版本</h3>
        <p class="<?php echo $php_version ? 'success' : 'error'; ?>">
            当前版本：<?php echo PHP_VERSION; ?> 
            <?php echo $php_version ? '√' : '× (需要 PHP 7.0.0 或更高版本)'; ?>
        </p>
    </div>
    
    <div class="check-item">
        <h3>PHP扩展</h3>
        <?php if (empty($extensions)): ?>
            <p class="success">所有必要的扩展都已安装 √</p>
        <?php else: ?>
            <p class="error">
                以下扩展未安装：<br>
                <?php echo implode('<br>', $extensions); ?> ×
            </p>
        <?php endif; ?>
    </div>
    
    <div class="check-item">
        <h3>目录权限</h3>
        <?php foreach ($directories as $dir => $status): ?>
            <p class="<?php echo ($status['exists'] && $status['writable']) ? 'success' : 'error'; ?>">
                <?php echo $dir; ?>: 
                <?php echo $status['message']; ?>
                <?php echo ($status['exists'] && $status['writable']) ? '√' : '×'; ?>
            </p>
        <?php endforeach; ?>
    </div>
    
    <div class="actions">
        <?php if ($can_install): ?>
            <a href="index.php?step=2" class="button next">下一步</a>
        <?php else: ?>
            <p class="error-message">请解决上述问题后继续安装</p>
            <button class="button" onclick="location.reload()">重新检测</button>
        <?php endif; ?>
    </div>
</div>