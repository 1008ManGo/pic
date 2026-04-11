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
                <li><a href="users.php">用户管理</a></li>
                <li><a href="channels.php" class="active">通道管理</a></li>
                <li><a href="sms_records.php">短信记录</a></li>
                <li><a href="../user/dashboard.php">返回用户端</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h1>SMPP通道管理</h1>
                <button class="btn btn-primary" onclick="showAddModal()" style="width: auto; padding: 10px 20px;">添加通道</button>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>通道ID</th>
                            <th>名称</th>
                            <th>IP:端口</th>
                            <th>最大TPS</th>
                            <th>状态</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody id="channelsTable">
                        <tr><td colspan="6" style="text-align: center; padding: 30px;">加载中...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div id="channelModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="background: white; max-width: 500px; margin: 100px auto; padding: 30px; border-radius: 10px;">
            <h2 id="modalTitle" style="margin-bottom: 20px;">添加通道</h2>
            <form id="channelForm">
                <input type="hidden" id="editId">
                <div class="form-group">
                    <label>通道ID</label>
                    <input type="text" id="channelId" required placeholder="如: CH_001">
                </div>
                <div class="form-group">
                    <label>通道名称</label>
                    <input type="text" id="channelName" required placeholder="如: 中国移动通道1">
                </div>
                <div class="form-group">
                    <label>IP地址</label>
                    <input type="text" id="ip" required placeholder="如: 156.226.175.3">
                </div>
                <div class="form-group">
                    <label>端口</label>
                    <input type="number" id="port" value="2775" required>
                </div>
                <div class="form-group">
                    <label>SMPP用户名</label>
                    <input type="text" id="username" required>
                </div>
                <div class="form-group">
                    <label>SMPP密码</label>
                    <input type="password" id="password" required>
                </div>
                <div class="form-group">
                    <label>最大TPS</label>
                    <input type="number" id="maxTps" value="50" required>
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
        async function loadChannels() {
            try {
                const result = await apiGet('/admin/channels');
                if (result.code === 0) {
                    renderTable(result.data);
                }
            } catch (e) {
                document.getElementById('channelsTable').innerHTML = 
                    `<tr><td colspan="6" style="text-align: center; color: red;">加载失败</td></tr>`;
            }
        }
        
        function renderTable(channels) {
            const tbody = document.getElementById('channelsTable');
            if (!channels.length) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">暂无数据</td></tr>';
                return;
            }
            
            tbody.innerHTML = channels.map(c => `
                <tr>
                    <td>${c.id}</td>
                    <td>${c.name}</td>
                    <td>${c.ip}:${c.port}</td>
                    <td>${c.max_tps}</td>
                    <td><span class="status-badge status-${c.status === 'active' ? 'success' : 'error'}">${c.status}</span></td>
                    <td>
                        <button onclick="editChannel(${JSON.stringify(c).replace(/"/g, '&quot;')})" style="margin-right: 5px; padding: 5px 10px; border: 1px solid #ddd; background: white; border-radius: 3px; cursor: pointer;">编辑</button>
                        <button onclick="deleteChannel('${c.id}')" style="padding: 5px 10px; border: 1px solid #f44336; background: white; border-radius: 3px; color: #f44336; cursor: pointer;">删除</button>
                    </td>
                </tr>
            `).join('');
        }
        
        function showAddModal() {
            document.getElementById('modalTitle').textContent = '添加通道';
            document.getElementById('channelForm').reset();
            document.getElementById('editId').value = '';
            document.getElementById('channelModal').style.display = 'block';
        }
        
        function editChannel(channel) {
            document.getElementById('modalTitle').textContent = '编辑通道';
            document.getElementById('editId').value = channel.id;
            document.getElementById('channelId').value = channel.id;
            document.getElementById('channelId').readOnly = true;
            document.getElementById('channelName').value = channel.name;
            document.getElementById('ip').value = channel.ip;
            document.getElementById('port').value = channel.port;
            document.getElementById('username').value = channel.username;
            document.getElementById('password').value = '';
            document.getElementById('maxTps').value = channel.max_tps;
            document.getElementById('channelModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('channelModal').style.display = 'none';
            document.getElementById('channelId').readOnly = false;
        }
        
        async function deleteChannel(id) {
            if (!confirm('确定要删除通道 ' + id + ' 吗?')) return;
            
            try {
                const result = await apiDelete(`/admin/channels/${id}`);
                if (result.code === 0) {
                    alert('删除成功');
                    loadChannels();
                } else {
                    alert('删除失败: ' + result.message);
                }
            } catch (e) {
                alert('请求失败');
            }
        }
        
        document.getElementById('channelForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const editId = document.getElementById('editId').value;
            const data = {
                id: document.getElementById('channelId').value,
                name: document.getElementById('channelName').value,
                ip: document.getElementById('ip').value,
                port: parseInt(document.getElementById('port').value),
                username: document.getElementById('username').value,
                password: document.getElementById('password').value,
                max_tps: parseInt(document.getElementById('maxTps').value)
            };
            
            if (!editId && !data.password) {
                alert('密码不能为空');
                return;
            }
            
            try {
                let result;
                if (editId) {
                    result = await apiPut(`/admin/channels/${editId}`, data);
                } else {
                    result = await apiPost('/admin/channels', data);
                }
                
                if (result.code === 0) {
                    alert('保存成功');
                    closeModal();
                    loadChannels();
                } else {
                    alert('保存失败: ' + result.message);
                }
            } catch (e) {
                alert('请求失败: ' + e.message);
            }
        });
        
        loadChannels();
    </script>
</body>
</html>
