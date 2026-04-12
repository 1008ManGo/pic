<?php
session_start();

if (!isset($_SESSION['token']) || ($_SESSION['user_info']['role'] ?? '') !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$pageTitle = '公告管理';
?>
<?php include 'header.php'; ?>

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
