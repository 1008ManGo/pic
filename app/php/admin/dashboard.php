<?php
session_start();

if (!isset($_SESSION['token']) || ($_SESSION['user_info']['role'] ?? '') !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$userInfo = $_SESSION['user_info'];
$pageTitle = '管理后台';
?>
<?php include 'header.php'; ?>

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
