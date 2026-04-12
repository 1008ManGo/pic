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
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <script>window.SESSION_TOKEN = '<?php echo $_SESSION["token"] ?? ""; ?>';</script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="bi bi-gear-fill"></i> 管理后台</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> 仪表盘</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="channels.php"><i class="bi bi-broadcast"></i> 通道管理</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="users.php"><i class="bi bi-people"></i> 用户管理</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="sms_records.php"><i class="bi bi-chat-left-text"></i> 短信记录</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../api/logout.php"><i class="bi bi-box-arrow-right"></i> 退出</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="bi bi-people"></i> 用户管理</h4>
                <button class="btn btn-primary" onclick="showAddModal()">
                    <i class="bi bi-plus-circle"></i> 添加用户
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>ID</th>
                                <th>用户名</th>
                                <th>余额</th>
                                <th>通道</th>
                                <th>国家</th>
                                <th>单价</th>
                                <th>状态</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="userList">
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">加载中...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">添加用户</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="userForm">
                        <input type="hidden" id="editId">
                        <div class="form-group">
                            <label>用户名</label>
                            <input type="text" class="form-control" id="username" required>
                        </div>
                        <div class="form-group">
                            <label>密码 <small class="text-muted">(留空则不修改)</small></label>
                            <input type="password" class="form-control" id="password">
                        </div>
                        <div class="form-group">
                            <label>余额</label>
                            <input type="number" step="0.0001" class="form-control" id="balance" value="0" required>
                        </div>
                        <div class="form-group">
                            <label>SMPP通道</label>
                            <select class="form-control" id="smpp_channel" required></select>
                        </div>
                        <div class="form-group">
                            <label>国家代码</label>
                            <select class="form-control" id="country_code" required></select>
                        </div>
                        <div class="form-group">
                            <label>价格 (元/条)</label>
                            <input type="number" step="0.0001" class="form-control" id="price" value="0.05" required>
                        </div>
                        <div class="form-group">
                            <label>角色</label>
                            <select class="form-control" id="role">
                                <option value="user">普通用户</option>
                                <option value="admin">管理员</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary" onclick="submitUser()">保存</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/jquery.min.js"></script>
    <script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="../js/api.js"></script>
    <script>
        async function loadUsers() {
            try {
                const result = await apiGet('/admin/users');
                if (result.code === 0) {
                    renderUsers(result.data.list);
                }
            } catch (e) {
                document.getElementById('userList').innerHTML = 
                    '<tr><td colspan="8" class="text-center text-danger">加载失败</td></tr>';
            }
        }
        
        function renderUsers(users) {
            const tbody = document.getElementById('userList');
            if (!users || users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">暂无用户</td></tr>';
                return;
            }
            tbody.innerHTML = users.map(u => 
                '<tr>' +
                    '<td>' + u.id + '</td>' +
                    '<td><i class="bi bi-person"></i> ' + u.username + '</td>' +
                    '<td><span class="text-success">' + u.balance.toFixed(4) + '</span></td>' +
                    '<td><code>' + u.smpp_channel + '</code></td>' +
                    '<td>' + u.country_code + '</td>' +
                    '<td>' + u.price + '</td>' +
                    '<td><span class="badge badge-' + (u.status === 1 ? 'success' : 'secondary') + '">' +
                    (u.status === 1 ? '正常' : '禁用') + '</span></td>' +
                    '<td>' +
                        '<button class="btn btn-sm btn-outline-primary mr-1" onclick="editUser(' + u.id + ')"><i class="bi bi-pencil"></i></button>' +
                        '<button class="btn btn-sm btn-outline-' + (u.status === 1 ? 'warning' : 'success') + '" onclick="toggleStatus(' + u.id + ', ' + (u.status === 1 ? 0 : 1) + ')">' +
                            '<i class="bi bi-' + (u.status === 1 ? 'slash' : 'check') + '"></i></button>' +
                    '</td>' +
                '</tr>'
            ).join('');
        }
        
        async function loadChannels() {
            try {
                const result = await apiGet('/admin/channels');
                if (result.code === 0) {
                    const select = document.getElementById('smpp_channel');
                    select.innerHTML = result.data.map(c => 
                        '<option value="' + c.id + '">' + c.id + ' - ' + c.name + '</option>'
                    ).join('');
                }
            } catch (e) {
                console.error('Failed to load channels:', e);
            }
        }
        
        async function loadCountries() {
            try {
                const result = await apiGet('/admin/countries');
                if (result.code === 0) {
                    const select = document.getElementById('country_code');
                    select.innerHTML = result.data.map(c => 
                        '<option value="' + c.code + '">' + c.name + ' (' + c.code + ')</option>'
                    ).join('');
                }
            } catch (e) {
                console.error('Failed to load countries:', e);
            }
        }
        
        function showAddModal() {
            document.getElementById('modalTitle').textContent = '添加用户';
            document.getElementById('userForm').reset();
            document.getElementById('editId').value = '';
            document.getElementById('price').value = '0.05';
            Promise.all([loadChannels(), loadCountries()]).then(() => $('#userModal').modal('show'));
        }
        
        async function editUser(id) {
            try {
                const result = await apiGet('/admin/users');
                if (result.code === 0) {
                    const user = result.data.list.find(u => u.id === id);
                    if (user) {
                        document.getElementById('modalTitle').textContent = '编辑用户';
                        document.getElementById('editId').value = user.id;
                        document.getElementById('username').value = user.username;
                        document.getElementById('password').value = '';
                        document.getElementById('balance').value = user.balance;
                        document.getElementById('price').value = user.price;
                        document.getElementById('role').value = user.role;
                        
                        await Promise.all([loadChannels(), loadCountries()]);
                        document.getElementById('smpp_channel').value = user.smpp_channel;
                        document.getElementById('country_code').value = user.country_code;
                        
                        $('#userModal').modal('show');
                    }
                }
            } catch (e) {
                alert('加载用户信息失败');
            }
        }
        
        async function submitUser() {
            const id = document.getElementById('editId').value;
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const balance = parseFloat(document.getElementById('balance').value);
            const smpp_channel = document.getElementById('smpp_channel').value;
            const country_code = document.getElementById('country_code').value;
            const price = parseFloat(document.getElementById('price').value;
            const role = document.getElementById('role').value;
            
            const data = { username, balance, smpp_channel, country_code, price, role };
            if (password) data.password = password;
            
            try {
                const result = id ? 
                    await apiPut('/admin/users/' + id, data) :
                    await apiPost('/admin/users', data);
                
                if (result.code === 0) {
                    $('#userModal').modal('hide');
                    loadUsers();
                } else {
                    alert('保存失败: ' + result.message);
                }
            } catch (e) {
                alert('保存失败: ' + e.message);
            }
        }
        
        async function toggleStatus(id, status) {
            try {
                const result = await apiPut('/admin/users/' + id, { status });
                if (result.code === 0) {
                    loadUsers();
                } else {
                    alert('操作失败: ' + result.message);
                }
            } catch (e) {
                alert('操作失败: ' + e.message);
            }
        }
        
        loadUsers();
    </script>
</body>
</html>
