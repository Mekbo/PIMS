// 安装程序脚本

// 测试数据库连接
function testConnection() {
    // 获取表单数据
    var formData = new FormData(document.getElementById('configForm'));
    
    // 发送Ajax请求测试连接
    fetch('test_connection.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
    })
    .catch(error => {
        alert('测试连接失败：' + error.message);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // 表单验证
    const configForm = document.getElementById('configForm');
    if (configForm) {
        configForm.addEventListener('submit', function(e) {
            const host = document.getElementById('host').value.trim();
            const port = document.getElementById('port').value.trim();
            const username = document.getElementById('username').value.trim();
            const database = document.getElementById('database').value.trim();
            
            if (!host || !port || !username || !database) {
                e.preventDefault();
                alert('请填写所有必填字段');
                return false;
            }
            
            // 验证端口号
            if (isNaN(port) || port < 1 || port > 65535) {
                e.preventDefault();
                alert('端口号必须是1-65535之间的数字');
                return false;
            }
        });
    }
    
    // 检查是否在安装完成页面
    if (window.location.href.includes('step=4')) {
        // 如果在第四步（安装完成页面），确保页面内容正确显示
        const completeDiv = document.querySelector('.install-complete');
        if (completeDiv) {
            // 安装完成页面已正确加载
            console.log('安装完成页面已加载');
        } else {
            // 如果安装完成页面没有正确加载，尝试重新加载
            console.log('安装完成页面未正确加载，尝试重新加载');
            window.location.reload();
        }
    }
});