<?php
session_start();

if (!isset($_SESSION['token'])) {
    header('Location: ../index.php');
    exit;
}

$userInfo = $_SESSION['user_info'];
$pageTitle = '仪表盘';
?>
<?php include 'header.php'; ?>

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
                        <div class="alert alert-info mb-0" id="announcementContent">
                            <i class="bi bi-info-circle"></i> 加载中...
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
    <script src="../js/api.js"></script>
    <script>
        async function loadAnnouncement() {
            try {
                const result = await apiGet('/user/announcement');
                if (result.code === 0 && result.data) {
                    document.getElementById('announcementContent').innerHTML = '<i class="bi bi-info-circle"></i> ' + (result.data.content || '暂无公告');
                }
            } catch (e) {
                document.getElementById('announcementContent').innerHTML = '<i class="bi bi-info-circle"></i> 暂无公告';
            }
        }
        loadAnnouncement();
    </script>
</body>
</html>
