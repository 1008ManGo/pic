<?php
session_start();

if (!isset($_SESSION['token']) || ($_SESSION['user_info']['role'] ?? '') !== 'admin') {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户管理 - 短信平台</title>
    <link rel="stylesheet" href="../css/style.css">
    <script>window.SESSION_TOKEN = '<?php echo $_SESSION["token"] ?? ""; ?>';</script>
</head>
<body>
    <div class="header">
        <h2>短信平台 - 管理后台</h2>
        <div class="user-info">
            <span>管理员</span>
            <a href="../api/logout.php" class="logout">退出</a>
        </div>
    </div>
    
    <div class="layout">
        <div class="sidebar">
            <ul>
                <li><a href="dashboard.php">仪表盘</a></li>
                <li><a href="users.php" class="active">用户管理</a></li>
                <li><a href="channels.php">通道管理</a></li>
                <li><a href="sms_records.php">短信记录</a></li>
                <li><a href="../user/dashboard.php">返回用户端</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h1>用户管理</h1>
                <button class="btn btn-primary" onclick="showAddModal()" style="width: auto; padding: 10px 20px;">添加用户</button>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>用户名</th>
                            <th>余额</th>
                            <th>通道</th>
                            <th>国家</th>
                            <th>价格</th>
                            <th>角色</th>
                            <th>状态</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody id="usersTable">
                        <tr><td colspan="9" style="text-align: center; padding: 30px;">加载中...</td></tr>
                    </tbody>
                </table>
                
                <div class="pagination" id="pagination"></div>
            </div>
        </div>
    </div>
    
    <div id="userModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="background: white; max-width: 500px; margin: 100px auto; padding: 30px; border-radius: 10px;">
            <h2 id="modalTitle" style="margin-bottom: 20px;">添加用户</h2>
            <form id="userForm">
                <input type="hidden" id="editId">
                <div class="form-group">
                    <label>用户名</label>
                    <input type="text" id="username" required>
                </div>
                <div class="form-group">
                    <label>密码</label>
                    <input type="password" id="password">
                </div>
                <div class="form-group">
                    <label>余额</label>
                    <input type="number" step="0.0001" id="balance" value="0">
                </div>
                <div class="form-group">
                    <label>SMPP通道</label>
                    <select id="smpp_channel" required></select>
                </div>
                <div class="form-group">
                    <label>国家代码</label>
                    <select id="country_code" required>
                        <option value="CN">中国 (CN)</option>
                        <option value="US">美国 (US)</option>
                        <option value="GB">英国 (GB)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>价格 (元/条)</label>
                    <input type="number" step="0.0001" id="price" value="0.05" required>
                </div>
                <div class="form-group">
                    <label>角色</label>
                    <select id="role">
                        <option value="user">普通用户</option>
                        <option value="admin">管理员</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">保存</button>
                    <button type="button" onclick="closeModal()" style="width: auto; padding: 12px 20px; background: #999; color: white; border: none; border-radius: 5px; cursor: pointer;">取消</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="../js/api.js"></script>
    <script>
        let currentPage = 1;
        
        async function loadUsers(page = 1) {
            currentPage = page;
            try {
                const result = await apiGet(`/admin/users?page=${page}&limit=20`);
                if (result.code === 0) {
                    renderTable(result.data.list);
                    renderPagination(result.data.total, result.data.page, result.data.limit);
                }
            } catch (e) {
                document.getElementById('usersTable').innerHTML = 
                    `<tr><td colspan="9" style="text-align: center; color: red;">加载失败</td></tr>`;
            }
        }
        
        function renderTable(users) {
            const tbody = document.getElementById('usersTable');
            if (!users.length) {
                tbody.innerHTML = '<tr><td colspan="9" style="text-align: center;">暂无数据</td></tr>';
                return;
            }
            
            tbody.innerHTML = users.map(u => `
                <tr>
                    <td>${u.id}</td>
                    <td>${u.username}</td>
                    <td>${u.balance}</td>
                    <td>${u.smpp_channel}</td>
                    <td>${u.country_code}</td>
                    <td>${u.price}</td>
                    <td>${u.role === 'admin' ? '管理员' : '用户'}</td>
                    <td><span class="status-badge status-${u.status === 1 ? 'success' : 'error'}">${u.status === 1 ? '正常' : '禁用'}</span></td>
                    <td>
                        <button onclick="editUser(${JSON.stringify(u).replace(/"/g, '&quot;')})" style="margin-right: 5px; padding: 5px 10px; border: 1px solid #ddd; background: white; border-radius: 3px; cursor: pointer;">编辑</button>
                        <button onclick="adjustBalance(${u.id}, ${u.balance})" style="margin-right: 5px; padding: 5px 10px; border: 1px solid #ddd; background: white; border-radius: 3px; cursor: pointer;">调余额</button>
                    </td>
                </tr>
            `).join('');
        }
        
        function renderPagination(total, page, limit) {
            const totalPages = Math.ceil(total / limit);
            const pagination = document.getElementById('pagination');
            
            let html = '';
            for (let i = 1; i <= totalPages; i++) {
                html += `<button class="${i === page ? 'active' : ''}" onclick="loadUsers(${i})">${i}</button>`;
            }
            pagination.innerHTML = html;
        }
        
        async function loadChannels() {
            try {
                const result = await apiGet('/admin/channels');
                if (result.code === 0) {
                    const select = document.getElementById('smpp_channel');
                    select.innerHTML = result.data.map(c => 
                        `<option value="${c.id}">${c.id} - ${c.name}</option>`
                    ).join('');
                }
            } catch (e) {
                console.error('Failed to load channels:', e);
            }
        }
        
        function showAddModal() {
            document.getElementById('modalTitle').textContent = '添加用户';
            document.getElementById('userForm').reset();
            document.getElementById('editId').value = '';
            document.getElementById('userModal').style.display = 'block';
            loadChannels();
        }
        
        function editUser(user) {
            document.getElementById('modalTitle').textContent = '编辑用户';
            document.getElementById('editId').value = user.id;
            document.getElementById('username').value = user.username;
            document.getElementById('password').value = '';
            document.getElementById('balance').value = user.balance;
            document.getElementById('price').value = user.price;
            document.getElementById('role').value = user.role;
            document.getElementById('userModal').style.display = 'block';
            loadChannels().then(() => {
                document.getElementById('smpp_channel').value = user.smpp_channel;
                document.getElementById('country_code').value = user.country_code;
            });
        }
        
        function closeModal() {
            document.getElementById('userModal').style.display = 'none';
        }
        
        function adjustBalance(userId, currentBalance) {
            const newBalance = prompt('请输入新余额:', currentBalance);
            if (newBalance !== null && newBalance !== '') {
                apiPut(`/admin/users/${userId}/balance`, { balance: parseFloat(newBalance) })
                    .then(result => {
                        if (result.code === 0) {
                            alert('余额调整成功');
                            loadUsers(currentPage);
                        } else {
                            alert('调整失败: ' + result.message);
                        }
                    });
            }
        }
        
        document.getElementById('userForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const editId = document.getElementById('editId').value;
            const data = {
                username: document.getElementById('username').value,
                smpp_channel: document.getElementById('smpp_channel').value,
                country_code: document.getElementById('country_code').value,
                price: parseFloat(document.getElementById('price').value),
                role: document.getElementById('role').value,
                balance: parseFloat(document.getElementById('balance').value || 0)
            };
            
            const password = document.getElementById('password').value;
            if (password) {
                data.password = password;
            }
            
            try {
                let result;
                if (editId) {
                    data.status = 1;
                    result = await apiPut(`/admin/users/${editId}`, data);
                } else {
                    data.password = password || '123456';
                    result = await apiPost('/admin/users', data);
                }
                
                if (result.code === 0) {
                    alert('保存成功');
                    closeModal();
                    loadUsers(currentPage);
                } else {
                    alert('保存失败: ' + result.message);
                }
            } catch (e) {
                alert('请求失败: ' + e.message);
            }
        });
        
        loadUsers(1);
    </script>
</body>
</html>
