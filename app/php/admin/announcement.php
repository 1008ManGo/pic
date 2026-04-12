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
    <title>公告管理 - 短信平台</title>
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
                        <a class="nav-link" href="sms_records.php"><i class="bi bi-chat-left-text"></i> 短信记录</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="announcement.php"><i class="bi bi-megaphone"></i> 公告管理</a>
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
        <div class="row">
            <div class="col-lg-6 mx-auto">
                <div class="card">
                    <div class="card-header bg-white">
                        <h4 class="mb-0"><i class="bi bi-megaphone"></i> 发布公告</h4>
                    </div>
                    <div class="card-body">
                        <form id="announcementForm">
                            <div class="form-group">
                                <label>公告标题</label>
                                <input type="text" class="form-control" id="title" required placeholder="请输入公告标题">
                            </div>
                            <div class="form-group">
                                <label>公告内容</label>
                                <textarea class="form-control" id="content" rows="5" required placeholder="请输入公告内容"></textarea>
                            </div>
                            <div class="form-group">
                                <label>类型</label>
                                <select class="form-control" id="type">
                                    <option value="info">通知</option>
                                    <option value="warning">警告</option>
                                    <option value="success">成功</option>
                                    <option value="danger">紧急</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="published" checked>
                                    <label class="custom-control-label" for="published">立即发布</label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="bi bi-send"></i> 发布公告
                            </button>
                        </form>
                        <div id="resultBox" class="mt-3" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/jquery.min.js"></script>
    <script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="../js/api.js"></script>
    <script>
        document.getElementById('announcementForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const title = document.getElementById('title').value;
            const content = document.getElementById('content').value;
            const type = document.getElementById('type').value;
            const published = document.getElementById('published').checked;
            
            const resultBox = document.getElementById('resultBox');
            resultBox.style.display = 'block';
            resultBox.innerHTML = '<div class="alert alert-info"><i class="bi bi-hourglass-split"></i> 正在提交...</div>';
            
            try {
                const result = await apiPost('/admin/announcement', {
                    title: title,
                    content: content,
                    type: type,
                    published: published
                });
                
                if (result.code === 0) {
                    resultBox.innerHTML = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> 公告发布成功！</div>';
                    document.getElementById('title').value = '';
                    document.getElementById('content').value = '';
                } else {
                    resultBox.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> 发布失败: ' + result.message + '</div>';
                }
            } catch (e) {
                resultBox.innerHTML = '<div class="alert alert-danger"><i class="bi bi-x-circle"></i> 请求失败: ' + e.message + '</div>';
            }
        });
    </script>
</body>
</html>
