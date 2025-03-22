/*备份恢复功能js脚本
 * backupRestore.js
 * 2025-03-18
 * by Arlvin.Ceon
*/

document.addEventListener('DOMContentLoaded', function() {
            loadBackups();

            document.getElementById('renameForm').addEventListener('submit', function(e) {
                e.preventDefault();
                renameBackupFile();
            });
        });

        function loadBackups(search = '') {
            fetch(`core/backupRestore.php?action=list&search=${encodeURIComponent(search)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayBackups(data.backups);
                    } else {
                        alert('加载备份列表失败：' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('加载备份列表时发生错误');
                });
        }

        function displayBackups(backups) {
            const tableBody = document.getElementById('backupTableBody');
            tableBody.innerHTML = '';
            backups.forEach(backup => {
                const row = tableBody.insertRow();
                row.innerHTML = `
                    <td>${backup.filename}</td>
                    <td>${formatFileSize(backup.size)}</td>
                    <td>${backup.date}</td>
                    <td>
                        <button onclick="downloadBackup('${backup.filename}')">下载</button>
                        <button onclick="restoreBackup('${backup.filename}')">恢复</button>
                        <button onclick="showRenameModal('${backup.filename}')">重命名</button>
                        <button onclick="deleteBackup('${backup.filename}')">删除</button>                   
                    </td>
                `;
            });
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        function downloadBackup(filename) {
            fetch(`core/backupRestore.php?action=download&filename=${encodeURIComponent(filename)}`)
                .then(response => response.blob())
                .then(blob => {
                    // 创建一个临时的URL对象
                    const url = window.URL.createObjectURL(blob);
                    // 创建一个临时的a标签
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = filename; // 设置下载文件名
                    document.body.appendChild(a);
                    a.click(); // 触发下载
                    // 清理
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                })
                .catch(error => {
                    console.error('下载失败:', error);
                    alert('下载文件失败，请重试');
                });
        }

        function searchBackups() {
            const searchTerm = document.getElementById('searchInput').value;
            loadBackups(searchTerm);
        }

        function createBackup() {
            fetch('core/backupRestore.php?action=backup', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadBackups();
                    alert('数据库备份创建成功');
                } else {
                    alert('创建备份失败：' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('创建备份时发生错误');
            });
        }

        function restoreBackup(filename) {
            if (confirm('确定要从这个备份文件恢复数据库吗？这将覆盖当前数据库中的所有数据。')) {
                fetch('core/backupRestore.php?action=restore', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `filename=${encodeURIComponent(filename)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('数据库恢复成功');
                    } else {
                        alert('恢复数据库失败：' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('恢复数据库时发生错误');
                });
            }
        }

        function showRenameModal(filename) {
            document.getElementById('oldFileName').value = filename;
            document.getElementById('newFileName').value = filename;
            document.getElementById('renameModal').style.display = 'block';
        }

        function closeRenameModal() {
            document.getElementById('renameModal').style.display = 'none';
        }

        function renameBackupFile() {
            const oldName = document.getElementById('oldFileName').value;
            const newName = document.getElementById('newFileName').value;

            fetch('core/backupRestore.php?action=rename', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `oldName=${encodeURIComponent(oldName)}&newName=${encodeURIComponent(newName)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeRenameModal();
                    loadBackups();
                    alert('备份文件重命名成功');
                } else {
                    alert('重命名备份文件失败：' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('重命名备份文件时发生错误');
            });
        }

        function deleteBackup(filename) {
            if (confirm('确定要删除这个备份文件吗？')) {
                fetch('core/backupRestore.php?action=delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `filename=${encodeURIComponent(filename)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadBackups();
                        alert('备份文件删除成功');
                    } else {
                        alert('删除备份文件失败：' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('删除备份文件时发生错误');
                });
            }
        }