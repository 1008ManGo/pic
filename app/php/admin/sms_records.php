<?php
session_start();

if (!isset($_SESSION['token']) || $_SESSION['user_info']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>短信记录 - 短信平台</title>
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
                        <a class="nav-link" href="users.php"><i class="bi bi-people"></i> 用户管理</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="sms_records.php"><i class="bi bi-chat-left-text"></i> 短信记录</a>
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
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="bi bi-chat-left-text"></i> 短信记录</h4>
                    <div class="form-inline">
                        <input type="text" id="taskIdSearch" class="form-control form-control-sm mr-2" placeholder="搜索任务ID">
                        <select id="statusFilter" class="form-control form-control-sm mr-2">
                            <option value="">全部状态</option>
                            <option value="pending">待处理</option>
                            <option value="success">成功</option>
                            <option value="failed">失败</option>
                        </select>
                        <button class="btn btn-primary btn-sm" onclick="loadRecords(1)">
                            <i class="bi bi-search"></i> 搜索
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>任务ID</th>
                                <th>用户</th>
                                <th>手机号码</th>
                                <th>通道</th>
                                <th>内容</th>
                                <th>状态</th>
                                <th>时间</th>
                            </tr>
                        </thead>
                        <tbody id="recordsList">
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">加载中...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <nav id="pagination" class="d-flex justify-content-center mt-3">
                    <ul class="pagination" id="paginationList"></ul>
                </nav>
            </div>
        </div>
    </div>

    <script src="../js/jquery.min.js"></script>
    <script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="../js/api.js"></script>
    <script>
        let currentPage = 1;
        const pageSize = 50;
        
        async function loadRecords(page) {
            currentPage = page;
            const status = document.getElementById('statusFilter').value;
            const taskId = document.getElementById('taskIdSearch').value.trim();
            
            try {
                let url = '/admin/sms/records?page=' + page + '&limit=' + pageSize;
                if (status) url += '&status=' + status;
                if (taskId) url += '&task_id=' + taskId;
                
                const result = await apiGet(url);
                
                if (result.code === 0) {
                    renderRecords(result.data.list);
                    renderPagination(result.data.page, result.data.total);
                }
            } catch (e) {
                document.getElementById('recordsList').innerHTML = 
                    '<tr><td colspan="7" class="text-center text-danger py-4">加载失败: ' + e.message + '</td></tr>';
            }
        }
        
        function renderRecords(records) {
            const tbody = document.getElementById('recordsList');
            
            if (!records || records.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4"><i class="bi bi-inbox" style="font-size: 2rem;"></i><p class="mt-2 mb-0">暂无记录</p></td></tr>';
                return;
            }
            
            tbody.innerHTML = records.map(r => {
                const statusMap = {
                    'pending': ['badge-secondary', '待处理'],
                    'success': ['badge-success', '成功'],
                    'failed': ['badge-danger', '失败']
                };
                const [cls, label] = statusMap[r.status] || ['badge-secondary', r.status];
                
                return '<tr>' +
                    '<td><small>' + r.task_id + '</small></td>' +
                    '<td><small>' + r.username + '</small></td>' +
                    '<td><code>' + r.phone + '</code></td>' +
                    '<td><small>' + r.channel_id + '</small></td>' +
                    '<td><small>' + (r.content ? (r.content.length > 20 ? r.content.substring(0, 20) + '...' : r.content) : '') + '</small></td>' +
                    '<td><span class="badge ' + cls + '">' + label + '</span></td>' +
                    '<td><small>' + r.created_at + '</small></td>' +
                    '</tr>';
            }).join('');
        }
        
        function renderPagination(page, total) {
            const totalPages = Math.ceil(total / pageSize);
            const paginationList = document.getElementById('paginationList');
            
            if (totalPages <= 1) {
                paginationList.innerHTML = '';
                return;
            }
            
            let html = '';
            
            html += '<li class="page-item ' + (page === 1 ? 'disabled' : '') + '">' +
                '<a class="page-link" href="#" onclick="loadRecords(' + (page - 1) + '); return false;">上一页</a></li>';
            
            for (let i = 1; i <= totalPages; i++) {
                if (i === 1 || i === totalPages || (i >= page - 2 && i <= page + 2)) {
                    html += '<li class="page-item ' + (i === page ? 'active' : '') + '">' +
                        '<a class="page-link" href="#" onclick="loadRecords(' + i + '); return false;">' + i + '</a></li>';
                } else if (i === page - 3 || i === page + 3) {
                    html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }
            
            html += '<li class="page-item ' + (page === totalPages ? 'disabled' : '') + '">' +
                '<a class="page-link" href="#" onclick="loadRecords(' + (page + 1) + '); return false;">下一页</a></li>';
            
            paginationList.innerHTML = html;
        }
        
        document.getElementById('statusFilter').addEventListener('change', () => loadRecords(1));
        document.getElementById('taskIdSearch').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') loadRecords(1);
        });
        
        loadRecords(1);
    </script>
</body>
</html>
