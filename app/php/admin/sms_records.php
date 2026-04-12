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
                        <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> 管理仪表盘</a>
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
        <div class="card">
            <div class="card-header bg-white">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="mb-0"><i class="bi bi-chat-left-text"></i> 短信记录</h4>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-success btn-sm" onclick="exportRecords()">
                            <i class="bi bi-download"></i> 导出CSV
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form id="filterForm" class="mb-3">
                    <div class="row align-items-end">
                        <div class="col-md-2 mb-2">
                            <label>用户名</label>
                            <input type="text" class="form-control form-control-sm" id="usernameFilter" placeholder="用户名">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label>手机号码</label>
                            <input type="text" class="form-control form-control-sm" id="phoneFilter" placeholder="模糊搜索">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label>发件人ID</label>
                            <input type="text" class="form-control form-control-sm" id="senderIdFilter" placeholder="发件人ID">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label>国家</label>
                            <input type="text" class="form-control form-control-sm" id="countryFilter" placeholder="国家代码">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label>状态</label>
                            <select class="form-control form-control-sm" id="statusFilter">
                                <option value="">全部</option>
                                <option value="pending">待处理</option>
                                <option value="success">成功</option>
                                <option value="failed">失败</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label>日期范围</label>
                            <div class="input-group input-group-sm">
                                <input type="date" class="form-control" id="startDate" placeholder="开始">
                                <div class="input-group-prepend"><span class="input-group-text">-</span></div>
                                <input type="date" class="form-control" id="endDate" placeholder="结束">
                            </div>
                        </div>
                        <div class="col-md-12 mt-2">
                            <button type="button" class="btn btn-primary btn-sm" onclick="loadRecords(1)">
                                <i class="bi bi-search"></i> 搜索
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm ml-2" onclick="resetFilters()">
                                <i class="bi bi-arrow-counterclockwise"></i> 重置
                            </button>
                        </div>
                    </div>
                </form>
                
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>任务ID</th>
                                <th>用户</th>
                                <th>手机号码</th>
                                <th>发件人ID</th>
                                <th>国家</th>
                                <th>内容</th>
                                <th>状态</th>
                                <th>时间</th>
                            </tr>
                        </thead>
                        <tbody id="recordsList">
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="bi bi-hourglass-split" style="font-size: 2rem;"></i>
                                    <p class="mt-2 mb-0">加载中...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-center mt-3">
                    <ul class="pagination mb-0" id="paginationList"></ul>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/jquery.min.js"></script>
    <script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="../js/api.js"></script>
    <script>
        let currentPage = 1;
        const pageSize = 50;
        let allRecords = [];
        let totalRecords = 0;
        
        async function loadRecords(page) {
            currentPage = page;
            
            try {
                let url = '/admin/sms/records?page=' + page + '&limit=' + pageSize;
                
                const result = await apiGet(url);
                
                if (result.code === 0) {
                    let records = result.data.list || [];
                    totalRecords = result.data.total || 0;
                    
                    const username = document.getElementById('usernameFilter').value.trim();
                    const phone = document.getElementById('phoneFilter').value.trim();
                    const senderId = document.getElementById('senderIdFilter').value.trim();
                    const country = document.getElementById('countryFilter').value.trim();
                    const status = document.getElementById('statusFilter').value;
                    const startDate = document.getElementById('startDate').value;
                    const endDate = document.getElementById('endDate').value;
                    
                    if (username) {
                        records = records.filter(r => r.username && r.username.includes(username));
                    }
                    if (phone) {
                        records = records.filter(r => r.phone && r.phone.includes(phone));
                    }
                    if (senderId) {
                        records = records.filter(r => r.sender_id && r.sender_id.includes(senderId));
                    }
                    if (country) {
                        records = records.filter(r => r.country_code && r.country_code.includes(country));
                    }
                    if (status) {
                        records = records.filter(r => r.status === status);
                    }
                    if (startDate) {
                        const start = new Date(startDate);
                        records = records.filter(r => new Date(r.created_at) >= start);
                    }
                    if (endDate) {
                        const end = new Date(endDate);
                        end.setHours(23, 59, 59);
                        records = records.filter(r => new Date(r.created_at) <= end);
                    }
                    
                    allRecords = records;
                    renderRecords(records);
                    renderPagination(page, totalRecords);
                }
            } catch (e) {
                document.getElementById('recordsList').innerHTML = 
                    '<tr><td colspan="8" class="text-center text-danger py-4">加载失败: ' + e.message + '</td></tr>';
            }
        }
        
        function renderRecords(records) {
            const tbody = document.getElementById('recordsList');
            
            if (!records || records.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4"><i class="bi bi-inbox" style="font-size: 2rem;"></i><p class="mt-2 mb-0">暂无记录</p></td></tr>';
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
                    '<td><small>' + (r.username || '-') + '</small></td>' +
                    '<td><code>' + r.phone + '</code></td>' +
                    '<td><small>' + (r.sender_id || '-') + '</small></td>' +
                    '<td><small>' + (r.country_code || '-') + '</small></td>' +
                    '<td><small>' + (r.content ? (r.content.length > 15 ? r.content.substring(0, 15) + '...' : r.content) : '') + '</small></td>' +
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
        
        function resetFilters() {
            document.getElementById('usernameFilter').value = '';
            document.getElementById('phoneFilter').value = '';
            document.getElementById('senderIdFilter').value = '';
            document.getElementById('countryFilter').value = '';
            document.getElementById('statusFilter').value = '';
            document.getElementById('startDate').value = '';
            document.getElementById('endDate').value = '';
            loadRecords(1);
        }
        
        function exportRecords() {
            if (allRecords.length === 0) {
                alert('没有可导出的记录');
                return;
            }
            
            let csv = '\uFEFF';
            csv += '任务ID,用户名,手机号码,发件人ID,国家,内容,状态,时间\n';
            
            const statusMap = {
                'pending': '待处理',
                'success': '成功',
                'failed': '失败'
            };
            
            allRecords.forEach(r => {
                const status = statusMap[r.status] || r.status;
                const content = (r.content || '').replace(/"/g, '""');
                csv += '"' + r.task_id + '","' + (r.username || '') + '","' + r.phone + '","' + (r.sender_id || '') + '","' + (r.country_code || '') + '","' + content + '","' + status + '","' + r.created_at + '"\n';
            });
            
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'admin_sms_records_' + new Date().toISOString().slice(0, 10) + '.csv';
            link.click();
        }
        
        loadRecords(1);
    </script>
</body>
</html>
