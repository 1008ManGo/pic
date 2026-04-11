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
    <title>发布公告 - 短信平台</title>
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
                <li><a href="sms_records.php">短信记录</a></li>
                <li><a href="announcement.php" class="active">发布公告</a></li>
                <li><a href="../user/dashboard.php">返回用户端</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="page-header">
                <h1>发布公告</h1>
            </div>
            
            <div class="send-form" style="max-width: 600px;">
                <form id="announcementForm">
                    <div class="form-group">
                        <label>公告标题</label>
                        <input type="text" id="title" required placeholder="请输入公告标题" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px;">
                    </div>
                    
                    <div class="form-group">
                        <label>公告内容</label>
                        <textarea id="content" required placeholder="请输入公告内容" style="width: 100%; min-height: 150px; padding: 12px; border: 1px solid #ddd; border-radius: 5px; resize: vertical;"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">发布公告</button>
                </form>
                
                <div id="resultBox" class="result-box"></div>
            </div>
        </div>
    </div>
    
    <script src="../js/api.js"></script>
    <script>
        document.getElementById('announcementForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const title = document.getElementById('title').value.trim();
            const content = document.getElementById('content').value.trim();
            
            if (!title || !content) {
                alert('请填写完整的公告信息');
                return;
            }
            
            const resultBox = document.getElementById('resultBox');
            resultBox.innerHTML = '<div style="color: blue;">正在发布...</div>';
            resultBox.classList.add('show');
            
            try {
                const result = await apiPost('/admin/announcement', { title, content });
                
                if (result.code === 0) {
                    resultBox.innerHTML = '<div class="alert alert-success">公告发布成功!</div>';
                    document.getElementById('announcementForm').reset();
                } else {
                    resultBox.innerHTML = `<div class="alert alert-error">发布失败: ${result.message}</div>`;
                }
            } catch (e) {
                resultBox.innerHTML = `<div class="alert alert-error">请求失败: ${e.message}</div>`;
            }
        });
    </script>
</body>
</html>
