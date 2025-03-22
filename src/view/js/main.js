/* 主页js脚本
 * main.js
 * 2025-03-18
 * by Arlvin.Ceon
*/

document.addEventListener('DOMContentLoaded', function() {
            // 获取系统概览数据
            fetch('core/dashboard.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // 更新管理员信息
                        document.getElementById('adminAvatar').src = data.admin.avatar || 'view/images/default-avatar.png';
                        document.getElementById('adminId').textContent = data.admin.admin_id;
                        document.getElementById('adminNickname').textContent = data.admin.nickname || data.admin.username;

                        // 更新系统概览
                        const overview = document.getElementById('systemOverview');
                        overview.innerHTML = `
                            <p><strong>账号总数：</strong> <a href="index.php?page=accountManage">${data.totalAccounts}<a/></p>
                            <p><strong>回收站项目：</strong> <a href="index.php?page=recycleBin">${data.recycleBinItems}<a/></p>
                            <p><strong>备份文件数：</strong><a href="index.php?page=backupRestore"> ${data.backupFiles}<a/></p>
                        `;

                        // 创建用户访问量图表
                        createVisitorChart(data.visitorData);

                        // 创建服务器压力图表
                        createServerLoadChart(data.serverLoadData);
                    } else {
                        document.getElementById('systemOverview').innerHTML = '<p>无法加载系统概览数据</p>';
                    }
                })
                .catch(error => {
                    document.getElementById('systemOverview').innerHTML = '<p>加载系统概览数据时出错</p>';
                    console.error('Error:', error);
                });
        });

        function createVisitorChart(data) {
            const ctx = document.getElementById('visitorChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: '访问量',
                        data: data.values,
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        function createServerLoadChart(data) {
            const ctx = document.getElementById('serverLoadChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'CPU使用率',
                        data: data.cpu,
                        backgroundColor: 'rgba(255, 99, 132, 0.5)'
                    }, {
                        label: '内存使用率',
                        data: data.memory,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }