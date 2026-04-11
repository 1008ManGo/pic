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
                <li><a href="channels.php">通道管理</a></li>
                <li><a href="sms_records.php" class="active">短信记录</a></li>
                <li><a href="../user/dashboard.php">返回用户端</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h1>短信记录</h1>
                <button class="btn btn-primary" onclick="exportRecords()" style="width: auto; padding: 10px 20px;">导出CSV</button>
            </div>
            
            <div class="table-container">
                <div style="margin-bottom: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
                    <select id="statusFilter" onchange="loadRecords(1)" style="padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                        <option value="">全部状态</option>
                        <option value="pending">待处理</option>
                        <option value="submitted">已提交</option>
                        <option value="success">成功</option>
                        <option value="failed">失败</option>
                        <option value="error">错误</option>
                    </select>
                    <input type="text" id="taskIdSearch" placeholder="搜索任务ID" style="padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                    <button onclick="loadRecords(1)" style="padding: 8px 15px; border: 1px solid #667eea; background: #667eea; color: white; border-radius: 5px; cursor: pointer;">搜索</button>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>任务ID</th>
                            <th>用户ID</th>
                            <th>手机号</th>
                            <th>内容</th>
                            <th>编码</th>
                            <th>状态</th>
                            <th>费用</th>
                            <th>时间</th>
                        </tr>
                    </thead>
                    <tbody id="recordsTable">
                        <tr><td colspan="8" style="text-align: center; padding: 30px;">加载中...</td></tr>
                    </tbody>
                </table>
                
                <div class="pagination" id="pagination"></div>
            </div>
        </div>
    </div>
    
    <script src="../js/api.js"></script>
    <script>
        let currentPage = 1;
        
        async function loadRecords(page = 1) {
            currentPage = page;
            const status = document.getElementById('statusFilter').value;
            const taskId = document.getElementById('taskIdSearch').value.trim();
            
            try {
                let url = `/admin/sms/records?page=${page}&limit=20`;
                if (status) url += `&status=${status}`;
                if (taskId) url += `&task_id=${taskId}`;
                
                const result = await apiGet(url);
                
                if (result.code === 0) {
                    renderTable(result.data.list);
                    renderPagination(result.data.total, result.data.page, result.data.limit);
                }
            } catch (e) {
                document.getElementById('recordsTable').innerHTML = 
                    `<tr><td colspan="8" style="text-align: center; color: red;">加载失败</td></tr>`;
            }
        }
        
        function renderTable(records) {
            const tbody = document.getElementById('recordsTable');
            
            if (!records || !records.length) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">暂无记录</td></tr>';
                return;
            }
            
            tbody.innerHTML = records.map(r => `
                <tr>
                    <td title="${r.task_id}">${r.task_id.substring(0, 15)}...</td>
                    <td>${r.user_id}</td>
                    <td>${r.phone}</td>
                    <td title="${r.content}">${r.content.substring(0, 20)}${r.content.length > 20 ? '...' : ''}</td>
                    <td>${r.encoding}</td>
                    <td><span class="status-badge status-${r.status}">${r.status}</span></td>
                    <td>${r.total_price}</td>
                    <td>${new Date(r.created_at).toLocaleString('zh-CN')}</td>
                </tr>
            `).join('');
        }
        
        function renderPagination(total, page, limit) {
            const totalPages = Math.ceil(total / limit);
            const pagination = document.getElementById('pagination');
            
            let html = '';
            for (let i = 1; i <= totalPages; i++) {
                html += `<button class="${i === page ? 'active' : ''}" onclick="loadRecords(${i})">${i}</button>`;
            }
            pagination.innerHTML = html;
        }
        
        function exportRecords() {
            window.open(API_BASE + '/admin/sms/export', '_blank');
        }
        
        loadRecords(1);
    </script>
</body>
</html>
