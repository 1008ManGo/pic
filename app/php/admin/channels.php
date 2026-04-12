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
    <title>通道管理 - 短信平台</title>
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
                        <a class="nav-link active" href="channels.php"><i class="bi bi-broadcast"></i> 通道管理</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php"><i class="bi bi-people"></i> 用户管理</a>
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
                <h4 class="mb-0"><i class="bi bi-broadcast"></i> 通道管理</h4>
                <button class="btn btn-primary" onclick="showAddModal()">
                    <i class="bi bi-plus-circle"></i> 添加通道
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>ID</th>
                                <th>名称</th>
                                <th>IP:端口</th>
                                <th>用户名</th>
                                <th>最大TPS</th>
                                <th>状态</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="channelList">
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">加载中...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="channelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">添加通道</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="channelForm">
                        <input type="hidden" id="editId">
                        <div class="form-group">
                            <label>通道ID</label>
                            <input type="text" class="form-control" id="channel_id" required placeholder="如: CH_001">
                        </div>
                        <div class="form-group">
                            <label>名称</label>
                            <input type="text" class="form-control" id="name" required placeholder="如: 测试通道">
                        </div>
                        <div class="form-group">
                            <label>IP地址</label>
                            <input type="text" class="form-control" id="ip" required placeholder="如: 156.226.175.3">
                        </div>
                        <div class="form-group">
                            <label>端口</label>
                            <input type="number" class="form-control" id="port" value="2775" required>
                        </div>
                        <div class="form-group">
                            <label>用户名</label>
                            <input type="text" class="form-control" id="username" required>
                        </div>
                        <div class="form-group">
                            <label>密码</label>
                            <input type="password" class="form-control" id="password">
                        </div>
                        <div class="form-group">
                            <label>最大TPS</label>
                            <input type="number" class="form-control" id="max_tps" value="100" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary" onclick="submitChannel()">保存</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/jquery.min.js"></script>
    <script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="../js/api.js"></script>
    <script>
        async function loadChannels() {
            try {
                const result = await apiGet('/admin/channels');
                if (result.code === 0) {
                    renderChannels(result.data);
                }
            } catch (e) {
                document.getElementById('channelList').innerHTML = 
                    '<tr><td colspan="7" class="text-center text-danger">加载失败</td></tr>';
            }
        }
        
        function renderChannels(channels) {
            const tbody = document.getElementById('channelList');
            if (!channels || channels.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">暂无通道</td></tr>';
                return;
            }
            tbody.innerHTML = channels.map(c => 
                '<tr>' +
                    '<td><code>' + c.id + '</code></td>' +
                    '<td><i class="bi bi-broadcast"></i> ' + c.name + '</td>' +
                    '<td><small>' + c.ip + ':' + c.port + '</small></td>' +
                    '<td>' + c.username + '</td>' +
                    '<td>' + c.max_tps + '</td>' +
                    '<td><span class="badge badge-' + (c.status === 'active' ? 'success' : 'secondary') + '">' +
                    (c.status === 'active' ? '在线' : '离线') + '</span></td>' +
                    '<td>' +
                        '<button class="btn btn-sm btn-outline-primary mr-1" onclick="editChannel(\'' + c.id + '\')"><i class="bi bi-pencil"></i></button>' +
                    '</td>' +
                '</tr>'
            ).join('');
        }
        
        function showAddModal() {
            document.getElementById('modalTitle').textContent = '添加通道';
            document.getElementById('channelForm').reset();
            document.getElementById('editId').value = '';
            document.getElementById('port').value = '2775';
            document.getElementById('max_tps').value = '100';
            $('#channelModal').modal('show');
        }
        
        async function editChannel(id) {
            try {
                const result = await apiGet('/admin/channels');
                if (result.code === 0) {
                    const channel = result.data.find(c => c.id === id);
                    if (channel) {
                        document.getElementById('modalTitle').textContent = '编辑通道';
                        document.getElementById('editId').value = channel.id;
                        document.getElementById('channel_id').value = channel.id;
                        document.getElementById('name').value = channel.name;
                        document.getElementById('ip').value = channel.ip;
                        document.getElementById('port').value = channel.port;
                        document.getElementById('username').value = channel.username;
                        document.getElementById('password').value = '';
                        document.getElementById('max_tps').value = channel.max_tps;
                        $('#channelModal').modal('show');
                    }
                }
            } catch (e) {
                alert('加载通道信息失败');
            }
        }
        
        async function submitChannel() {
            const id = document.getElementById('editId').value;
            const data = {
                name: document.getElementById('name').value,
                ip: document.getElementById('ip').value,
                port: parseInt(document.getElementById('port').value),
                username: document.getElementById('username').value,
                max_tps: parseInt(document.getElementById('max_tps').value)
            };
            
            const password = document.getElementById('password').value;
            if (password) data.password = password;
            
            try {
                const result = id ? 
                    await apiPut('/admin/channels/' + id, data) :
                    await apiPost('/admin/channels', { ...data, id: document.getElementById('channel_id').value });
                
                if (result.code === 0) {
                    $('#channelModal').modal('hide');
                    loadChannels();
                } else {
                    alert('保存失败: ' + result.message);
                }
            } catch (e) {
                alert('保存失败: ' + e.message);
            }
        }
        
        loadChannels();
    </script>
</body>
</html>
