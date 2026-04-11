<?php
session_start();

if (!isset($_SESSION['token'])) {
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
        <h2>短信平台</h2>
        <div class="user-info">
            <span>欢迎, <?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></span>
            <a href="../api/logout.php" class="logout">退出</a>
        </div>
    </div>
    
    <div class="layout">
        <div class="sidebar">
            <ul>
                <li><a href="dashboard.php">仪表盘</a></li>
                <li><a href="send_sms.php">发送短信</a></li>
                <li><a href="records.php" class="active">短信记录</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="page-header">
                <h1>短信记录</h1>
            </div>
            
            <div class="table-container">
                <div style="margin-bottom: 15px;">
                    <label>状态筛选: </label>
                    <select id="statusFilter" onchange="loadRecords(1)" style="padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                        <option value="">全部</option>
                        <option value="pending">待处理</option>
                        <option value="submitted">已提交</option>
                        <option value="success">成功</option>
                        <option value="failed">失败</option>
                        <option value="error">错误</option>
                    </select>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>任务ID</th>
                            <th>手机号码</th>
                            <th>内容</th>
                            <th>状态</th>
                            <th>计费条数</th>
                            <th>创建时间</th>
                        </tr>
                    </thead>
                    <tbody id="recordsTable">
                        <tr><td colspan="6" style="text-align: center; padding: 30px;">加载中...</td></tr>
                    </tbody>
                </table>
                
                <div class="pagination" id="pagination"></div>
            </div>
        </div>
    </div>
    
    <script src="../js/api.js"></script>
    <script>
        let currentPage = 1;
        let totalPages = 1;
        
        async function loadRecords(page) {
            currentPage = page;
            const status = document.getElementById('statusFilter').value;
            
            try {
                let url = `/sms/records?page=${page}&limit=20`;
                if (status) url += `&status=${status}`;
                
                const result = await apiGet(url);
                
                if (result.code === 0) {
                    renderTable(result.data.list);
                    renderPagination(result.data.total, result.data.page, result.data.limit);
                }
            } catch (e) {
                document.getElementById('recordsTable').innerHTML = 
                    `<tr><td colspan="6" style="text-align: center; color: red;">加载失败: ${e.message}</td></tr>`;
            }
        }
        
        function renderTable(records) {
            const tbody = document.getElementById('recordsTable');
            
            if (!records || records.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 30px;">暂无记录</td></tr>';
                return;
            }
            
            tbody.innerHTML = records.map(r => `
                <tr>
                    <td title="${r.task_id}">${r.task_id.substring(0, 15)}...</td>
                    <td>${r.phone}</td>
                    <td title="${r.content}">${r.content.substring(0, 30)}${r.content.length > 30 ? '...' : ''}</td>
                    <td><span class="status-badge status-${r.status}">${getStatusText(r.status)}</span></td>
                    <td>${r.sms_count}</td>
                    <td>${new Date(r.created_at).toLocaleString('zh-CN')}</td>
                </tr>
            `).join('');
        }
        
        function getStatusText(status) {
            const map = {
                'pending': '待处理',
                'submitted': '已提交',
                'success': '成功',
                'failed': '失败',
                'error': '错误',
                'unknown': '未知'
            };
            return map[status] || status;
        }
        
        function renderPagination(total, page, limit) {
            totalPages = Math.ceil(total / limit);
            const pagination = document.getElementById('pagination');
            
            let html = '';
            for (let i = 1; i <= totalPages; i++) {
                html += `<button class="${i === page ? 'active' : ''}" onclick="loadRecords(${i})">${i}</button>`;
            }
            pagination.innerHTML = html;
        }
        
        loadRecords(1);
    </script>
</body>
</html>
