<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>短信平台</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <script>window.SESSION_TOKEN = '<?php echo $_SESSION["token"] ?? ""; ?>';</script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-gear-fill"></i> 管理后台
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> 仪表盘
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'channels' ? 'active' : ''; ?>" href="channels.php">
                            <i class="bi bi-broadcast"></i> 通道管理
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'users' ? 'active' : ''; ?>" href="users.php">
                            <i class="bi bi-people"></i> 用户管理
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'sms_records' ? 'active' : ''; ?>" href="sms_records.php">
                            <i class="bi bi-chat-left-text"></i> 短信记录
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'announcement' ? 'active' : ''; ?>" href="announcement.php">
                            <i class="bi bi-megaphone"></i> 公告管理
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../user/dashboard.php">
                            <i class="bi bi-person"></i> 用户面板
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../api/logout.php">
                            <i class="bi bi-box-arrow-right"></i> 退出
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
