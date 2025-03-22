/*回收站清空js脚本
 * recycleBin.js
 * 2025-03-18
 * by Arlvin.Ceon
*/

let currentPage = 1;
        const perPage = 15;
        let passwordCallback = null;

        document.addEventListener('DOMContentLoaded', function() {
            loadDeletedAccounts();

            document.getElementById('passwordForm').addEventListener('submit', function(e) {
                e.preventDefault();
                if (passwordCallback) {
                    passwordCallback(document.getElementById('adminPassword').value);
                }
                closePasswordModal();
            });
        });

        function loadDeletedAccounts(page = 1, search = '') {
            currentPage = page;
            fetch(`core/recycleBin.php?action=list&page=${page}&perPage=${perPage}&search=${encodeURIComponent(search)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayDeletedAccounts(data.accounts);
                        displayPagination(data.total, data.pages, data.current);
                    } else {
                        alert('加载已删除账号列表失败：' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('加载已删除账号列表时发生错误');
                });
        }

        function displayDeletedAccounts(accounts) {
            const tableBody = document.getElementById('deletedAccountsTableBody');
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
                        <button onclick="restoreAccount(${account.account_id})">恢复</button>
                        <button onclick="permanentlyDeleteAccount(${account.account_id})">彻底删除</button>
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
                        loadDeletedAccounts(1, document.getElementById('searchInput').value);
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
                        loadDeletedAccounts(current - 1, document.getElementById('searchInput').value);
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
                    loadDeletedAccounts(i, document.getElementById('searchInput').value);
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
                        loadDeletedAccounts(current + 1, document.getElementById('searchInput').value);
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
                        loadDeletedAccounts(pages, document.getElementById('searchInput').value);
                    });
                }
                pagination.appendChild(lastLink);
            }

            // 显示分页信息
            paginationInfo.textContent = `共${pages}页，当前第${current}页`;
        }

        function searchDeletedAccounts() {
            const searchTerm = document.getElementById('searchInput').value;
            loadDeletedAccounts(1, searchTerm);
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

        function showPasswordModal(callback) {
            passwordCallback = callback;
            document.getElementById('adminPassword').value = '';
            document.getElementById('passwordModal').style.display = 'block';
        }

        function closePasswordModal() {
            document.getElementById('passwordModal').style.display = 'none';
            passwordCallback = null;
        }

        function restoreAccount(accountId) {
            fetch('core/recycleBin.php?action=restore', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ids=${encodeURIComponent(JSON.stringify([accountId]))}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadDeletedAccounts(currentPage, document.getElementById('searchInput').value);
                    alert('账号恢复成功');
                } else {
                    alert('恢复账号失败：' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('恢复账号时发生错误');
            });
        }
		
		//批量已恢复已经删除账号
		
		 function restoreAccountAll() {
		  const selectedIds = getSelectedAccountIds();
            if (selectedIds.length === 0) {
                alert('请选择要恢复的账号');
                return;
            }
		 
            fetch('core/recycleBin.php?action=restore', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ids=${encodeURIComponent(JSON.stringify(selectedIds))}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadDeletedAccounts(currentPage, document.getElementById('searchInput').value);
                    alert('账号恢复成功');
                } else {
                    alert('恢复账号失败：' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('恢复账号时发生错误');
            });
        }
		
        function permanentlyDeleteAccount(accountId) {
            showPasswordModal(password => {
                fetch('core/recycleBin.php?action=delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `ids=${encodeURIComponent(JSON.stringify([accountId]))}&password=${encodeURIComponent(password)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadDeletedAccounts(currentPage, document.getElementById('searchInput').value);
                        alert('账号已彻底删除');
                    } else {
                        alert('删除账号失败：' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('删除账号时发生错误');
                });
            });
        }

        function emptyRecycleBin() {
            const selectedIds = getSelectedAccountIds();
            if (selectedIds.length === 0) {
                alert('请选择要删除的账号');
                return;
            }

            if (confirm('确定要彻底删除选中的账号吗？此操作不可恢复！')) {
                showPasswordModal(password => {
                    fetch('core/recycleBin.php?action=delete', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `ids=${encodeURIComponent(JSON.stringify(selectedIds))}&password=${encodeURIComponent(password)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            loadDeletedAccounts(currentPage, document.getElementById('searchInput').value);
                            alert('选中的账号已彻底删除');
                        } else {
                            alert('删除账号失败：' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('删除账号时发生错误');
                    });
                });
            }
        }