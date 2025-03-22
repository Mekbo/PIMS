 /*账号信息管理js处理脚本
  * accountManage.js
  * 2025-03-18
  * by Arlvin.Ceon
 */
 
 let currentPage = 1;
        const perPage = 15;

        document.addEventListener('DOMContentLoaded', function() {
            loadAccounts();

            document.getElementById('accountForm').addEventListener('submit', function(e) {
                e.preventDefault();
                saveAccount();
            });
        });

        function loadAccounts(page = 1, search = '') {
            currentPage = page;
            fetch(`core/accountManage.php?action=list&page=${page}&perPage=${perPage}&search=${encodeURIComponent(search)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayAccounts(data.accounts);
                        displayPagination(data.total, data.pages, data.current);
                    } else {
                        alert('加载账号列表失败：' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('加载账号列表时发生错误');
                });
        }

        function displayAccounts(accounts) {
            const tableBody = document.getElementById('accountsTableBody');
            tableBody.innerHTML = '';
            accounts.forEach(account => {
                const row = tableBody.insertRow();
                row.innerHTML = `
				<td><input type="checkbox" class="account-checkbox" value="${account.account_id}"></td>
                    <td>${account.account_name}</td>
                    <td>${account.account_password}</td>
                    <td>${account.phone || ''}</td>
                    <td>${account.email || ''}</td>
                    <td>${account.platform || ''}</td>
                    <td>${account.register_date || ''}</td>
                    <td>					
                        <button onclick="editAccount(${account.account_id})">查看/编辑</button>
                        <button onclick="deleteAccount(${account.account_id})">删除</button>
                    </td>
                `;
            });
        }

        function displayPagination(total, pages, current) {
            const pagination = document.getElementById('pagination');
            const paginationInfo = document.getElementById('paginationInfo');
            pagination.innerHTML = '';
            
            // 添加"首页"和"上一页"按钮
            if (pages > 1) {
                // 添加"首页"按钮
                const firstLink = document.createElement('a');
                firstLink.href = '#';
                firstLink.textContent = '首页';
                firstLink.classList.add('page-nav');
                if (current === 1) {
                    firstLink.classList.add('disabled');
                    firstLink.style.opacity = '0.5';
                    firstLink.style.pointerEvents = 'none';
                } else {
                    firstLink.addEventListener('click', (e) => {
                        e.preventDefault();
                        loadAccounts(1, document.getElementById('searchInput').value);
                    });
                }
                pagination.appendChild(firstLink);

                // 添加"上一页"按钮
                const prevLink = document.createElement('a');
                prevLink.href = '#';
                prevLink.textContent = '上一页';
                prevLink.classList.add('page-nav');
                if (current === 1) {
                    prevLink.classList.add('disabled');
                    prevLink.style.opacity = '0.5';
                    prevLink.style.pointerEvents = 'none';
                } else {
                    prevLink.addEventListener('click', (e) => {
                        e.preventDefault();
                        loadAccounts(current - 1, document.getElementById('searchInput').value);
                    });
                }
                pagination.appendChild(prevLink);
            }
            
            // 显示页码，最多显示10页
            let startPage = Math.max(1, current - 4);
            let endPage = Math.min(pages, startPage + 9);
            
            // 调整起始页，确保最多显示10页
            if (endPage - startPage < 9) {
                startPage = Math.max(1, endPage - 9);
            }
            
            for (let i = startPage; i <= endPage; i++) {
                const link = document.createElement('a');
                link.href = '#';
                link.textContent = i;
                if (i === current) {
                    link.classList.add('active');
                }
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    loadAccounts(i, document.getElementById('searchInput').value);
                });
                pagination.appendChild(link);
            }
            
            // 添加"下一页"和"尾页"按钮
            if (pages > 1) {
                const nextLink = document.createElement('a');
                nextLink.href = '#';
                nextLink.textContent = '下一页';
                nextLink.classList.add('page-nav');
                if (current === pages) {
                    nextLink.classList.add('disabled');
                    nextLink.style.opacity = '0.5';
                    nextLink.style.pointerEvents = 'none';
                } else {
                    nextLink.addEventListener('click', (e) => {
                        e.preventDefault();
                        loadAccounts(current + 1, document.getElementById('searchInput').value);
                    });
                }
                pagination.appendChild(nextLink);

                // 添加"尾页"按钮
                const lastLink = document.createElement('a');
                lastLink.href = '#';
                lastLink.textContent = '尾页';
                lastLink.classList.add('page-nav');
                if (current === pages) {
                    lastLink.classList.add('disabled');
                    lastLink.style.opacity = '0.5';
                    lastLink.style.pointerEvents = 'none';
                } else {
                    lastLink.addEventListener('click', (e) => {
                        e.preventDefault();
                        loadAccounts(pages, document.getElementById('searchInput').value);
                    });
                }
                pagination.appendChild(lastLink);
            }

            // 显示分页信息
            paginationInfo.textContent = `共${pages}页，当前第${current}页`;
        }

        function searchAccounts() {
            const searchTerm = document.getElementById('searchInput').value;
            loadAccounts(1, searchTerm);
        }
		
		 function toggleSelectAll() {
            const checkboxes = document.getElementsByClassName('account-checkbox');
            const selectAllCheckbox = document.getElementById('selectAll');
            for (let checkbox of checkboxes) {
                checkbox.checked = selectAllCheckbox.checked;
            }
        }

		 function getSelectedAccountIds() {
            const checkboxes = document.getElementsByClassName('account-checkbox');
            const selectedIds = [];
            for (let checkbox of checkboxes) {
                if (checkbox.checked) {
                    selectedIds.push(checkbox.value);
                }
            }
            return selectedIds;
        }
		
        function showAddAccountModal() {
            document.getElementById('modalTitle').textContent = '添加账号';
            document.getElementById('accountForm').reset();
            document.getElementById('accountId').value = '';
            document.getElementById('accountModal').style.display = 'block';
            document.getElementById('copyInfoBtn').style.display = 'none'; // 隐藏复制信息按钮
        }

        function editAccount(accountId) {
            console.log(`正在获取账号ID: ${accountId} 的信息`);
            document.getElementById('copyInfoBtn').style.display = 'inline-block'; // 显示复制信息按钮
            
            fetch(`core/accountManage.php?action=get&id=${accountId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    console.log('获取到响应，正在解析');
                    const contentType = response.headers.get('content-type');
                    console.log('Content-Type:', contentType);
                    return response.text().then(text => {
                        console.log('服务器响应:', text);
                        try {
                            const data = JSON.parse(text);
                            console.log('JSON解析成功:', data);
                            return data;
                        } catch (e) {
                            console.error('JSON解析错误:', e);
                            console.error('服务器响应不是有效的JSON:', text);
                            throw new Error('服务器返回了无效的数据格式');
                        }
                    });
                })
                .then(data => {
                    console.log('开始处理数据:', data);
                    if (data.success && data.account) {
                        console.log('账号信息获取成功:', data.account);
                        try {
                            // 设置模态框标题
                            const modalTitle = document.getElementById('modalTitle');
                            modalTitle.textContent = '编辑账号';
                            console.log('设置模态框标题成功');

                            // 创建一个函数来安全地设置输入字段的值
                            const safeSetValue = (id, value) => {
                                try {
                                    const element = document.getElementById(id);
                                    if (element) {
                                        element.value = value || '';
                                        console.log(`设置 ${id} 成功:`, value);
                                    } else {
                                        console.error(`未找到元素: ${id}`);
                                    }
                                } catch (err) {
                                    console.error(`设置 ${id} 时出错:`, err);
                                }
                            };

                            // 使用安全设置函数设置所有字段
                            const fields = {
                                'accountId': data.account.account_id,
                                'accountName': data.account.account_name,
                                'accountPassword': data.account.account_password,
                                'nickname': data.account.nickname,
                                'phone': data.account.phone,
                                'email': data.account.email,
                                'securityQuestion': data.account.security_question,
                                'platform': data.account.platform,
                                'url': data.account.url,
                                'registerDate': data.account.register_date,
                                'remarks': data.account.remarks
                            };

                            Object.entries(fields).forEach(([id, value]) => {
                                safeSetValue(id, value);
                            });

                            // 显示模态框
                            const modal = document.getElementById('accountModal');
                            if (modal) {
                                modal.style.display = 'block';
                                console.log('模态框显示成功');
                            } else {
                                console.error('未找到模态框元素');
                            }
                        } catch (err) {
                            console.error('设置表单数据时发生错误:', err);
                            alert('设置表单数据时发生错误: ' + err.message);
                        }
                    } else {
                        console.error('加载账号信息失败:', data);
                        alert('加载账号信息失败：' + (data.message || '未知错误'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('加载账号信息时发生错误: ' + error.message);
                });
        }

        function closeAccountModal() {
            document.getElementById('accountModal').style.display = 'none';
        }

        function copyAccountInfo() {
            // 获取所有表单字段的值
            const fields = {
                '账号名': document.getElementById('accountName').value,
                '密码': document.getElementById('accountPassword').value,
                '昵称': document.getElementById('nickname').value,
                '手机': document.getElementById('phone').value,
                '邮箱': document.getElementById('email').value,
                '密保问题': document.getElementById('securityQuestion').value,
                '平台': document.getElementById('platform').value,
                '网址': document.getElementById('url').value,
                '注册日期': document.getElementById('registerDate').value,
                '备注': document.getElementById('remarks').value
            };

            // 构建要复制的文本
            let copyText = '';
            for (const [key, value] of Object.entries(fields)) {
                if (value) { // 只包含有值的字段
                    copyText += `${key}：${value}\n`;
                }
            }

            // 创建临时textarea元素
            const textarea = document.createElement('textarea');
            textarea.value = copyText;
            document.body.appendChild(textarea);

            // 选中并复制文本
            textarea.select();
            try {
                document.execCommand('copy');
                alert('账号信息已复制到剪贴板！');
            } catch (err) {
                console.error('复制失败:', err);
                alert('复制失败，请重试');
            }

            // 移除临时元素
            document.body.removeChild(textarea);
        }

        function saveAccount() {
            const formData = new FormData(document.getElementById('accountForm'));
            const accountId = document.getElementById('accountId').value;
			//const accountId = formData.get('accountId');
            const action = accountId ? 'update' : 'add';

            // 验证表单数据
            const accountName = formData.get('account_name');
            const accountPassword = formData.get('account_password');

            if (!accountName || !accountPassword) {
                alert('账号名和密码不能为空');
                return;
            }

            fetch(`core/accountManage.php?action=${action}&account_id=${accountId}`, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('服务器响应不是有效的JSON:', text);
                        throw new Error('服务器返回了无效的数据格式');
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    closeAccountModal();
                    loadAccounts(currentPage, document.getElementById('searchInput').value);
                    alert(action === 'add' ? '账号添加成功' : '账号更新成功');
                } else {
                    alert('保存账号失败：' + (data.message || '未知错误'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('保存账号时发生错误: ' + error.message);
            });
        }
        
        function deleteAccount(accountId) {
            if (confirm('确定要删除这个账号吗？')) {
                fetch(`core/accountManage.php?action=delete&id=${accountId}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadAccounts(currentPage, document.getElementById('searchInput').value);
                        alert('账号已移至回收站');
                    } else {
                        alert('删除账号失败：' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('删除账号时发生错误');
                });
            }
        }
		
		//批量删除账号
		
        // 显示导出模态框
        function showExportModal() {
            if (confirm('确定要导出所有账号信息吗？')) {
                document.getElementById('exportModal').style.display = 'block';
                document.getElementById('exportForm').reset();
            }
        }

        // 关闭导出模态框
        function closeExportModal() {
            document.getElementById('exportModal').style.display = 'none';
        }

        // 显示导入模态框
        function showImportModal() {
            const importModal = document.getElementById('importModal');
            const modalContent = importModal.querySelector('.modal-content');
            
            // 添加进度条容器（如果不存在）
            if (!document.getElementById('importProgressContainer')) {
                const progressContainer = document.createElement('div');
                progressContainer.id = 'importProgressContainer';
                progressContainer.style.display = 'none';
                progressContainer.innerHTML = `
                    <div style="margin: 20px 0;">
                        <div style="margin-bottom: 10px; text-align: center;">
                            <span id="importProgressText">准备导入...</span>
                            <span id="importProgressPercent">0%</span>
                        </div>
                        <div style="background: #f0f0f0; border-radius: 4px; overflow: hidden;">
                            <div id="importProgressBar" style="width: 0%; height: 20px; background: linear-gradient(45deg, #4CAF50 25%, #45a049 25%, #45a049 50%, #4CAF50 50%, #4CAF50 75%, #45a049 75%); background-size: 20px 20px; transition: width 0.3s ease-in-out; animation: progressBarMove 1s linear infinite;"></div>
                        </div>
                    </div>
                `;
                modalContent.insertBefore(progressContainer, document.getElementById('importForm').nextSibling);
                
                // 添加动画样式
                if (!document.getElementById('importProgressStyle')) {
                    const style = document.createElement('style');
                    style.id = 'importProgressStyle';
                    style.textContent = `
                        @keyframes progressBarMove {
                            0% {
                                background-position: 0 0;
                            }
                            100% {
                                background-position: 20px 0;
                            }
                        }
                        #importProgressBar.success {
                            background: #4CAF50 !important;
                            animation: none !important;
                        }
                        #importProgressBar.error {
                            background: #f44336 !important;
                            animation: none !important;
                        }
                    `;
                    document.head.appendChild(style);
                }
            }
            
            importModal.style.display = 'block';
            // 重置表单和进度条
            document.getElementById('importForm').reset();
            document.getElementById('importProgressContainer').style.display = 'none';
            document.getElementById('importProgressBar').style.width = '0%';
            document.getElementById('importProgressBar').className = '';
            document.getElementById('importProgressText').textContent = '准备导入...';
            document.getElementById('importProgressPercent').textContent = '0%';
        }

        // 关闭导入模态框
        function closeImportModal() {
            const importModal = document.getElementById('importModal');
            const progressContainer = document.getElementById('importProgressContainer');
            if (progressContainer) {
                progressContainer.style.display = 'none';
            }
            importModal.style.display = 'none';
        }

        // 处理导入表单提交
        document.getElementById('importForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const fileInput = document.getElementById('importFile');
            const file = fileInput.files[0];
            
            if (!file) {
                alert('请选择一个CSV文件');
                return;
            }

            // 显示加载动画和进度条
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = '导入中...';
            submitBtn.disabled = true;
            
            // 显示进度条
            const progressContainer = document.getElementById('importProgressContainer');
            const progressBar = document.getElementById('importProgressBar');
            const progressText = document.getElementById('importProgressText');
            const progressPercent = document.getElementById('importProgressPercent');
            
            progressContainer.style.display = 'block';
            progressBar.style.width = '0%';
            progressBar.className = '';
            progressText.textContent = '准备导入...';
            progressPercent.textContent = '0%';
            
            // 模拟进度增长
            let progress = 0;
            const progressInterval = setInterval(() => {
                if (progress < 90) {
                    progress += Math.random() * 15;
                    if (progress > 90) progress = 90;
                    progressBar.style.width = progress + '%';
                    progressPercent.textContent = Math.round(progress) + '%';
                }
            }, 500);

            const formData = new FormData();
            formData.append('file', file);

            fetch('core/accountManage.php?action=importAll', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error(text);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // 显示100%进度
                    clearInterval(progressInterval);
                    const progressBar = document.getElementById('importProgressBar');
                    const progressText = document.getElementById('importProgressText');
                    const progressPercent = document.getElementById('importProgressPercent');
                    
                    progressBar.style.width = '100%';
                    progressBar.className = 'success';
                    progressText.textContent = '导入成功！';
                    progressPercent.textContent = '100%';
                    
                    // 延迟关闭弹窗，让用户能看到完成状态
                    setTimeout(() => {
                        alert(data.message || '导入成功');
                        closeImportModal();
                        loadAccounts(); // 重新加载账号列表
                    }, 800);
                } else {
                    // 显示错误状态
                clearInterval(progressInterval);
                const progressBar = document.getElementById('importProgressBar');
                const progressText = document.getElementById('importProgressText');
                progressBar.className = 'error';
                progressText.textContent = '导入失败';
                alert('导入失败：' + (data.message || '未知错误'));
                }
            })
            .catch(error => {
                // 显示错误状态
                clearInterval(progressInterval);
                const progressBar = document.getElementById('importProgressBar');
                const progressText = document.getElementById('importProgressText');
                progressBar.className = 'error';
                progressText.textContent = '导入失败';
                console.error('Error:', error);
                alert('导入过程中发生错误: ' + error.message);
            })
            .finally(() => {
                // 恢复按钮状态
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        });

        // 导出账号信息
        document.getElementById('exportForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const password = document.getElementById('exportPassword').value;
            const systemType = document.getElementById('systemType').value;

            fetch('core/accountManage.php?action=export', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `password=${encodeURIComponent(password)}&systemType=${encodeURIComponent(systemType)}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json().then(data => {
                        throw new Error(data.message || '导出失败');
                    });
                }
                return response.blob();
            })
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `accounts_export_${new Date().toISOString().slice(0,10)}.csv`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                closeExportModal();
                alert('导出成功！');
            })
            .catch(error => {
                console.error('Error:', error);
                alert('导出失败: ' + error.message);
            });
        });

        function deletAllAccount() {
            const selectedIds = getSelectedAccountIds();
            if (selectedIds.length === 0) {
                alert('请选择要删除的账号');
                return;
            }

            if (confirm('确定要删除选中的账号吗？')) {
               
                    fetch('core/accountManage.php?action=deleteAll', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `ids=${encodeURIComponent(JSON.stringify(selectedIds))}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            loadAccounts(currentPage, document.getElementById('searchInput').value);
                            alert('选中的账号已删除');
                        } else {
                            alert('删除账号失败：' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('删除账号时发生错误');
                    });
               
            }
        }