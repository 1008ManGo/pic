<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$isAdmin = isset($_SESSION['user_info']) && ($_SESSION['user_info']['role'] ?? '') === 'admin';
$isUserPage = strpos($_SERVER['PHP_SELF'], '/user/') !== false;
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
    <nav class="navbar navbar-expand-lg navbar-dark <?php echo $isAdmin ? 'bg-dark' : 'bg-primary'; ?>">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-<?php echo $isAdmin ? 'gear-fill' : 'envelope-fill'; ?>"></i> 
                <?php echo $isAdmin ? '管理后台' : '短信平台'; ?>
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mr-auto">
                    <?php if ($isAdmin): ?>
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
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> 仪表盘
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'send_sms' ? 'active' : ''; ?>" href="send_sms.php">
                            <i class="bi bi-send"></i> 发送短信
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'records' ? 'active' : ''; ?>" href="records.php">
                            <i class="bi bi-clock-history"></i> 短信记录
                        </a>
                    </li>
                    <?php if ($isAdmin): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../admin/dashboard.php">
                            <i class="bi bi-gear"></i> 管理后台
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['user_info'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" data-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['user_info']['username'] ?? ''); ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <?php if (!$isAdmin): ?>
                            <a class="dropdown-item" href="#">
                                <i class="bi bi-wallet2"></i> 余额: <?php echo number_format($_SESSION['user_info']['balance'] ?? 0, 4); ?> 元
                            </a>
                            <a class="dropdown-item" href="#">
                                <i class="bi bi-geo-alt"></i> 国家: <?php echo $_SESSION['user_info']['country_code'] ?? ''; ?>
                            </a>
                            <div class="dropdown-divider"></div>
                            <?php endif; ?>
                            <a class="dropdown-item text-danger" href="../api/logout.php">
                                <i class="bi bi-box-arrow-right"></i> 退出
                            </a>
                        </div>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
