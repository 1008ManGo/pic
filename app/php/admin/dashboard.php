<?php
session_start();

if (!isset($_SESSION['token']) || ($_SESSION['user_info']['role'] ?? '') !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$userInfo = $_SESSION['user_info'];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理后台 - 短信平台</title>
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
                        <a class="nav-link active" href="dashboard.php"><i class="bi bi-speedometer2"></i> 管理仪表盘</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="channels.php"><i class="bi bi-broadcast"></i> 通道管理</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php"><i class="bi bi-people"></i> 用户管理</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="sms_records.php"><i class="bi bi-chat-left-text"></i> 短信记录</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="announcement.php"><i class="bi bi-megaphone"></i> 公告管理</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../user/dashboard.php"><i class="bi bi-person"></i> 用户面板</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../api/logout.php"><i class="bi bi-box-arrow-right"></i> 退出</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-3">
        <h4 class="mb-3"><i class="bi bi-speedometer2"></i> 管理后台概览</h4>
        
        <div class="row">
            <div class="col-6 col-md-3 mb-3">
                <div class="card border-primary">
                    <div class="card-body text-center py-2">
                        <i class="bi bi-people text-primary"></i>
                        <h6 class="text-muted mt-1 mb-0">用户数</h6>
                        <h4 class="mb-0" id="userCount">-</h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-3">
                <div class="card border-success">
                    <div class="card-body text-center py-2">
                        <i class="bi bi-broadcast text-success"></i>
                        <h6 class="text-muted mt-1 mb-0">通道数</h6>
                        <h4 class="mb-0" id="channelCount">-</h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-3">
                <div class="card border-warning">
                    <div class="card-body text-center py-2">
                        <i class="bi bi-clock-history text-warning"></i>
                        <h6 class="text-muted mt-1 mb-0">今日发送</h6>
                        <h4 class="mb-0" id="todayCount">-</h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-3">
                <div class="card border-info">
                    <div class="card-body text-center py-2">
                        <i class="bi bi-check-circle text-info"></i>
                        <h6 class="text-muted mt-1 mb-0">成功率</h6>
                        <h4 class="mb-0" id="successRate">-</h4>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-2">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-broadcast"></i> 通道状态</h5>
                    </div>
                    <div class="card-body">
                        <div id="channelList" class="list-group">
                            <div class="text-center text-muted py-3">加载中...</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-people"></i> 用户列表</h5>
                    </div>
                    <div class="card-body">
                        <div id="userList" class="list-group">
                            <div class="text-center text-muted py-3">加载中...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/jquery.min.js"></script>
    <script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="../js/api.js"></script>
    <script>
        async function loadDashboard() {
            try {
                const [usersResult, channelsResult] = await Promise.all([
                    apiGet('/admin/users'),
                    apiGet('/admin/channels')
                ]);
                
                if (usersResult.code === 0) {
                    document.getElementById('userCount').textContent = usersResult.data.total || 0;
                    
                    const topUsers = (usersResult.data.list || []).slice(0, 5);
                    document.getElementById('userList').innerHTML = topUsers.length ? 
                        topUsers.map(u => '<div class="list-group-item d-flex justify-content-between align-items-center">' +
                            '<span><i class="bi bi-person"></i> ' + u.username + '</span>' +
                            '<span class="badge badge-' + (u.status === 1 ? 'success' : 'secondary') + '">' +
                            (u.status === 1 ? '正常' : '禁用') + '</span></div>').join('') :
                        '<div class="text-muted">暂无用户</div>';
                }
                
                if (channelsResult.code === 0) {
                    document.getElementById('channelCount').textContent = channelsResult.data.length || 0;
                    
                    document.getElementById('channelList').innerHTML = (channelsResult.data || []).map(c => 
                        '<div class="list-group-item d-flex justify-content-between align-items-center">' +
                            '<span><i class="bi bi-broadcast"></i> ' + c.name + '</span>' +
                            '<span class="badge badge-' + (c.status === 'active' ? 'success' : 'secondary') + '">' +
                            (c.status === 'active' ? '在线' : '离线') + '</span></div>'
                    ).join('') || '<div class="text-muted">暂无通道</div>';
                }
                
                document.getElementById('todayCount').textContent = '-';
                document.getElementById('successRate').textContent = '-';
            } catch (e) {
                console.error('Failed to load dashboard:', e);
            }
        }
        
        loadDashboard();
    </script>
</body>
</html>
