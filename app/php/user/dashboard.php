<?php
session_start();

if (!isset($_SESSION['token'])) {
    header('Location: ../index.php');
    exit;
}

$userInfo = $_SESSION['user_info'];
$isAdmin = ($userInfo['role'] ?? '') === 'admin';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户仪表盘 - 短信平台</title>
    <link rel="stylesheet" href="../css/style.css">
    <script>window.SESSION_TOKEN = '<?php echo $_SESSION["token"] ?? ""; ?>';</script>
</head>
<body>
    <div class="header">
        <h2>短信平台</h2>
        <div class="user-info">
            <span>欢迎, <?php echo htmlspecialchars($userInfo['username']); ?></span>
            <span>余额: <strong id="balance"><?php echo $userInfo['balance']; ?></strong></span>
            <a href="../api/logout.php" class="logout">退出</a>
        </div>
    </div>
    
    <div class="layout">
        <div class="sidebar">
            <ul>
                <li><a href="dashboard.php" class="active">仪表盘</a></li>
                <li><a href="send_sms.php">发送短信</a></li>
                <li><a href="records.php">短信记录</a></li>
                <?php if ($isAdmin): ?>
                <li><a href="../admin/dashboard.php">管理后台</a></li>
                <?php endif; ?>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="page-header">
                <h1>仪表盘</h1>
            </div>
            
            <div class="dashboard-cards">
                <div class="card">
                    <h3>当前余额</h3>
                    <div class="value" id="balance-card"><?php echo $userInfo['balance']; ?></div>
                </div>
                <div class="card">
                    <h3>单价 (元/条)</h3>
                    <div class="value"><?php echo $userInfo['price']; ?></div>
                </div>
                <div class="card">
                    <h3>SMPP通道</h3>
                    <div class="value"><?php echo htmlspecialchars($userInfo['smpp_channel']); ?></div>
                </div>
                <div class="card">
                    <h3>国家代码</h3>
                    <div class="value"><?php echo htmlspecialchars($userInfo['country_code']); ?></div>
                </div>
            </div>
            
            <div class="card">
                <h3>今日发送统计</h3>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 15px;">
                    <div>
                        <div style="font-size: 24px; font-weight: bold; color: #667eea;" id="today-total">-</div>
                        <div style="color: #666; font-size: 12px;">今日发送</div>
                    </div>
                    <div>
                        <div style="font-size: 24px; font-weight: bold; color: #4caf50;" id="today-success">-</div>
                        <div style="color: #666; font-size: 12px;">成功</div>
                    </div>
                    <div>
                        <div style="font-size: 24px; font-weight: bold; color: #f44336;" id="today-failed">-</div>
                        <div style="color: #666; font-size: 12px;">失败</div>
                    </div>
                    <div>
                        <div style="font-size: 24px; font-weight: bold; color: #ff9800;" id="today-pending">-</div>
                        <div style="color: #666; font-size: 12px;">待处理</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../js/api.js"></script>
    <script>
        async function loadDashboard() {
            try {
                const data = await apiGet('/dashboard');
                document.getElementById('balance').textContent = data.balance;
                document.getElementById('balance-card').textContent = data.balance;
            } catch (e) {
                console.error('Failed to load dashboard:', e);
            }
        }
        
        loadDashboard();
    </script>
</body>
</html>
