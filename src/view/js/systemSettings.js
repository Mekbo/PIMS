/* 系统设置js脚本
 * systemSetting.js
 * 2025-03-18
 * by Arlvin.Ceon
*/

document.addEventListener('DOMContentLoaded', function() {
    loadAdminInfo();
    loadBackupSettings();

    document.getElementById('adminInfoForm').addEventListener('submit', function(e) {
        e.preventDefault();
        updateAdminInfo();
    });

    document.getElementById('passwordForm').addEventListener('submit', function(e) {
        e.preventDefault();
        updatePassword();
    });

    document.getElementById('backupSettingsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        updateBackupSettings();
    });
});

function loadAdminInfo() {
    fetch('core/systemSettings.php?action=getAdminInfo')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('nickname').value = data.admin.nickname || '';
                document.getElementById('currentAvatar').src = data.admin.avatar || 'view/images/icon.png';
            } else {
                alert('加载管理员信息失败：' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('加载管理员信息时发生错误');
        });
}

function loadBackupSettings() {
    fetch('core/systemSettings.php?action=getBackupSettings')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('backupPath').value = data.backupPath || 'backups/';
            } else {
                alert('加载备份设置失败：' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('加载备份设置时发生错误');
        });
}

function updateAdminInfo() {
    const formData = new FormData(document.getElementById('adminInfoForm'));
    
    fetch('core/systemSettings.php?action=updateAdminInfo', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('管理员信息更新成功');
            loadAdminInfo();
        } else {
            alert('更新管理员信息失败：' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('更新管理员信息时发生错误');
    });
}

function updatePassword() {
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    if (newPassword !== confirmPassword) {
        alert('两次输入的新密码不一致');
        return;
    }

    fetch('core/systemSettings.php?action=updatePassword', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `currentPassword=${encodeURIComponent(currentPassword)}&newPassword=${encodeURIComponent(newPassword)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('密码修改成功');
            document.getElementById('passwordForm').reset();
        } else {
            alert('修改密码失败：' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('修改密码时发生错误');
    });
}

function updateBackupSettings() {
    const backupPath = document.getElementById('backupPath').value;

    fetch('core/systemSettings.php?action=updateBackupSettings', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `backupPath=${encodeURIComponent(backupPath)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('备份设置更新成功');
        } else {
            alert('更新备份设置失败：' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('更新备份设置时发生错误');
    });
}

// 清除缓存
function clearCache(type) {
    if (!confirm('确定要清除' + (type === 'all' ? '所有' : type) + '缓存吗？')) {
        return;
    }

    fetch('core/systemSettings.php?action=clearCache', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'type=' + encodeURIComponent(type)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('缓存清除成功！');
            // 如果清除了系统缓存，重新加载管理员信息和备份设置
            if (type === 'all' || type === 'system') {
                loadAdminInfo();
                loadBackupSettings();
            }
        } else {
            alert('缓存清除失败：' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('清除缓存时发生错误：' + error);
    });
}