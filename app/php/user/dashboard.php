<?php
session_start();

if (!isset($_SESSION['token'])) {
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
    <title>仪表盘 - 短信平台</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <script>window.SESSION_TOKEN = '<?php echo $_SESSION["token"] ?? ""; ?>';</script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="bi bi-envelope-fill"></i> 短信平台</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php"><i class="bi bi-speedometer2"></i> 仪表盘</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="send_sms.php"><i class="bi bi-send"></i> 发送短信</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="records.php"><i class="bi bi-clock-history"></i> 短信记录</a>
                    </li>
                    <?php if ($userInfo['role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../admin/dashboard.php"><i class="bi bi-gear"></i> 管理后台</a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" data-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($userInfo['username']); ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="#">
                                <i class="bi bi-wallet2"></i> 余额: <?php echo number_format($userInfo['balance'], 4); ?> 元
                            </a>
                            <a class="dropdown-item" href="#">
                                <i class="bi bi-geo-alt"></i> 国家: <?php echo $userInfo['country_code']; ?>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-danger" href="../api/logout.php">
                                <i class="bi bi-box-arrow-right"></i> 退出
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-3">
        <div class="row">
            <div class="col-6 col-md-3 mb-3">
                <div class="card border-primary">
                    <div class="card-body text-center py-2">
                        <i class="bi bi-wallet2 text-primary"></i>
                        <h6 class="text-muted mt-1 mb-0">账户余额</h6>
                        <h5 class="mb-0 text-primary"><?php echo number_format($userInfo['balance'], 4); ?> 元</h5>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-3">
                <div class="card border-success">
                    <div class="card-body text-center py-2">
                        <i class="bi bi-currency-dollar text-success"></i>
                        <h6 class="text-muted mt-1 mb-0">短信单价</h6>
                        <h5 class="mb-0 text-success"><?php echo $userInfo['price']; ?> 元/条</h5>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-3">
                <div class="card border-info">
                    <div class="card-body text-center py-2">
                        <i class="bi bi-globe text-info"></i>
                        <h6 class="text-muted mt-1 mb-0">服务国家</h6>
                        <h5 class="mb-0 text-info"><?php echo $userInfo['country_code']; ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-3">
                <div class="card border-warning">
                    <div class="card-body text-center py-2">
                        <i class="bi bi-person-badge text-warning"></i>
                        <h6 class="text-muted mt-1 mb-0">账户类型</h6>
                        <h5 class="mb-0 text-warning"><?php echo $userInfo['role'] === 'admin' ? '管理员' : '普通用户'; ?></h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-2">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-megaphone"></i> 公告</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle"></i> 欢迎使用短信平台服务。如有疑问请联系客服。
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-2">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-lightbulb"></i> 使用指南</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="d-flex align-items-start">
                                    <div class="bg-primary bg-opacity-10 p-2 rounded mr-3">
                                        <i class="bi bi-1-circle-fill text-primary"></i>
                                    </div>
                                    <div>
                                        <h6>发送短信</h6>
                                        <p class="text-muted mb-0 small">进入发送短信页面，输入号码和内容即可发送</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-start">
                                    <div class="bg-success bg-opacity-10 p-2 rounded mr-3">
                                        <i class="bi bi-2-circle-fill text-success"></i>
                                    </div>
                                    <div>
                                        <h6>批量导入</h6>
                                        <p class="text-muted mb-0 small">支持从 TXT 文件批量导入号码</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-start">
                                    <div class="bg-warning bg-opacity-10 p-2 rounded mr-3">
                                        <i class="bi bi-3-circle-fill text-warning"></i>
                                    </div>
                                    <div>
                                        <h6>查看记录</h6>
                                        <p class="text-muted mb-0 small">在短信记录页面查看发送历史和状态</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/jquery.min.js"></script>
    <script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
