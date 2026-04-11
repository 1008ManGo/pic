<?php
session_start();

if (!isset($_SESSION['token'])) {
    header('Location: ../index.php');
    exit;
}

$userInfo = $_SESSION['user_info'] ?? [];
if (($userInfo['role'] ?? '') !== 'admin') {
    header('Location: ../user/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理后台 - 短信平台</title>
    <link rel="stylesheet" href="../css/style.css">
    <script>
        window.SESSION_TOKEN = '<?php echo $_SESSION["token"] ?? ""; ?>';
        console.log('[DEBUG] Page loaded, SESSION_TOKEN length:', (window.SESSION_TOKEN || '').length);
        console.log('[DEBUG] SESSION_TOKEN first 50 chars:', (window.SESSION_TOKEN || '').substring(0, 50));
    </script>
</head>
<body>
    <div class="header">
        <h2>短信平台 - 管理后台</h2>
        <div class="user-info">
            <span>管理员: <?php echo htmlspecialchars($userInfo['username'] ?? ''); ?></span>
            <a href="../api/logout.php" class="logout">退出</a>
        </div>
    </div>
    
    <div class="layout">
        <div class="sidebar">
            <ul>
                <li><a href="dashboard.php" class="active">仪表盘</a></li>
                <li><a href="users.php">用户管理</a></li>
                <li><a href="channels.php">通道管理</a></li>
                <li><a href="sms_records.php">短信记录</a></li>
                <li><a href="announcement.php">发布公告</a></li>
                <li><a href="../user/dashboard.php">返回用户端</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="page-header">
                <h1>管理仪表盘</h1>
            </div>
            
            <div class="dashboard-cards">
                <div class="card">
                    <h3>用户总数</h3>
                    <div class="value" id="totalUsers">-</div>
                </div>
                <div class="card">
                    <h3>通道总数</h3>
                    <div class="value" id="totalChannels">-</div>
                </div>
                <div class="card">
                    <h3>今日发送</h3>
                    <div class="value success" id="todaySms">-</div>
                </div>
                <div class="card">
                    <h3>成功率</h3>
                    <div class="value" id="successRate">-</div>
                </div>
            </div>
            
            <div class="table-container" style="margin-top: 20px;">
                <h3 style="margin-bottom: 15px;">最近短信记录</h3>
                <table>
                    <thead>
                        <tr>
                            <th>任务ID</th>
                            <th>用户</th>
                            <th>手机号</th>
                            <th>状态</th>
                            <th>时间</th>
                        </tr>
                    </thead>
                    <tbody id="recentRecords">
                        <tr><td colspan="5" style="text-align: center;">加载中...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script src="../js/api.js"></script>
    <script>
        async function loadDashboard() {
            try {
                const [usersRes, channelsRes, recordsRes] = await Promise.all([
                    apiGet('/admin/users?page=1&limit=1'),
                    apiGet('/admin/channels'),
                    apiGet('/admin/sms/records?page=1&limit=10')
                ]);
                
                document.getElementById('totalUsers').textContent = usersRes.data?.total || 0;
                document.getElementById('totalChannels').textContent = channelsRes.data?.length || 0;
                
                if (recordsRes.data?.list) {
                    renderRecentRecords(recordsRes.data.list);
                }
            } catch (e) {
                console.error('Failed to load dashboard:', e);
            }
        }
        
        function renderRecentRecords(records) {
            const tbody = document.getElementById('recentRecords');
            if (!records.length) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">暂无记录</td></tr>';
                return;
            }
            
            tbody.innerHTML = records.map(r => `
                <tr>
                    <td title="${r.task_id}">${r.task_id.substring(0, 12)}...</td>
                    <td>${r.user_id}</td>
                    <td>${r.phone}</td>
                    <td><span class="status-badge status-${r.status}">${r.status}</span></td>
                    <td>${new Date(r.created_at).toLocaleString('zh-CN')}</td>
                </tr>
            `).join('');
        }
        
        loadDashboard();
    </script>
</body>
</html>
