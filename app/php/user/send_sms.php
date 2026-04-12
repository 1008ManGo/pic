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
    <title>发送短信 - 短信平台</title>
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
                        <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> 仪表盘</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="send_sms.php"><i class="bi bi-send"></i> 发送短信</a>
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

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-white">
                        <h4 class="mb-0"><i class="bi bi-send"></i> 发送短信</h4>
                    </div>
                    <div class="card-body">
                        <form id="sendForm">
                            <div class="form-group">
                                <label for="phones"><i class="bi bi-phone"></i> 手机号码</label>
                                <textarea class="form-control" id="phones" rows="3" 
                                    placeholder="+8613800000000&#10;+8613900001111&#10;或: +8613800000000, +8613900001111" required></textarea>
                                <div class="mt-2">
                                    <label class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-file-earmark-text"></i> 从TXT文件导入
                                        <input type="file" id="phoneFile" accept=".txt" style="display: none;">
                                    </label>
                                    <span id="phoneFileName" class="ml-2 text-muted"></span>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="sender_id"><i class="bi bi-person-badge"></i> 发件人ID</label>
                                <input type="text" class="form-control" id="sender_id" 
                                    placeholder="3-11位字母或数字，留空使用系统默认" 
                                    maxlength="11" pattern="[A-Za-z0-9]{3,11}">
                                <small class="form-text text-muted">选填，发件人ID将显示在接收方手机上</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="content"><i class="bi bi-chat-left-text"></i> 短信内容</label>
                                <textarea class="form-control" id="content" rows="4" 
                                    placeholder="请输入短信内容" required maxlength="500"></textarea>
                                <div class="d-flex justify-content-between mt-2">
                                    <span class="text-muted"><span id="charCount">0</span> / 500 字符</span>
                                    <span class="text-info" id="smsCount"></span>
                                </div>
                            </div>
                            
                            <div class="card bg-light mb-3">
                                <div class="card-body py-2">
                                    <h6 class="mb-2"><i class="bi bi-calculator"></i> 费用预览</h6>
                                    <div class="row text-center small mb-2">
                                        <div class="col-3">
                                            <div class="text-muted">号码数量</div>
                                            <div class="h6 mb-0" id="phoneCount">0</div>
                                        </div>
                                        <div class="col-3">
                                            <div class="text-muted">短信条数</div>
                                            <div class="h6 mb-0" id="totalSms">0</div>
                                        </div>
                                        <div class="col-3">
                                            <div class="text-muted">单价</div>
                                            <div class="h6 mb-0"><?php echo $userInfo['price']; ?> 元</div>
                                        </div>
                                        <div class="col-3">
                                            <div class="text-muted">发件人ID</div>
                                            <div class="h6 mb-0" id="senderIdDisplay">-</div>
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <span class="h5 mb-0 text-primary">总费用: <span id="totalCost">0</span> 元</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card bg-warning bg-light mb-3">
                                <div class="card-body py-2">
                                    <h6 class="mb-2"><i class="bi bi-info-circle"></i> 编码说明 (实际扣费以系统为准)</h6>
                                    <div class="row text-center small">
                                        <div class="col-4">
                                            <div class="text-muted">英文/数字</div>
                                            <div class="h6 mb-0">≤160字符 = 1条</div>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-muted">中文/混合</div>
                                            <div class="h6 mb-0">≤70字符 = 1条</div>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-muted">长短信</div>
                                            <div class="h6 mb-0">自动拆分计费</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg btn-block">
                                <i class="bi bi-send"></i> 提交发送
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
        const pricePerSms = <?php echo $userInfo['price']; ?>;
        
        function parsePhones(text) {
            return text.split(/[\n,]+/)
                .map(p => p.trim())
                .filter(p => p.length > 0);
        }
        
        function countSmsParts(content) {
            const len = content.length;
            if (len <= 70) return 1;
            if (len <= 134) return 2;
            if (len <= 201) return 3;
            if (len <= 268) return 4;
            return Math.ceil(len / 67);
        }
        
        function validateSenderId(senderId) {
            if (!senderId) return true;
            return /^[A-Za-z0-9]{3,11}$/.test(senderId);
        }
        
        function updatePreview() {
            const phones = parsePhones(document.getElementById('phones').value);
            const content = document.getElementById('content').value;
            const senderId = document.getElementById('sender_id').value.trim();
            
            const phoneCount = phones.length;
            const smsCount = countSmsParts(content);
            const totalCost = phoneCount * smsCount * pricePerSms;
            
            document.getElementById('phoneCount').textContent = phoneCount;
            document.getElementById('totalSms').textContent = phoneCount * smsCount;
            document.getElementById('totalCost').textContent = totalCost.toFixed(4);
            document.getElementById('charCount').textContent = content.length;
            document.getElementById('smsCount').textContent = smsCount > 1 ? '(将分成 ' + smsCount + ' 条)' : '';
            document.getElementById('senderIdDisplay').textContent = senderId || '-';
        }
        
        document.getElementById('phones').addEventListener('input', updatePreview);
        document.getElementById('content').addEventListener('input', updatePreview);
        document.getElementById('sender_id').addEventListener('input', updatePreview);
        
        document.getElementById('phoneFile').addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            
            document.getElementById('phoneFileName').textContent = '已选择: ' + file.name;
            
            try {
                const text = await file.text();
                const phones = parsePhones(text);
                
                const phoneInput = document.getElementById('phones');
                if (phoneInput.value && !phoneInput.value.endsWith('\n') && !phoneInput.value.endsWith(',')) {
                    phoneInput.value += '\n';
                }
                phoneInput.value += phones.join('\n');
                
                updatePreview();
            } catch (err) {
                alert('读取文件失败: ' + err.message);
            }
        });
        
        document.getElementById('sendForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const phones = parsePhones(document.getElementById('phones').value);
            const content = document.getElementById('content').value;
            const senderId = document.getElementById('sender_id').value.trim();
            
            if (phones.length === 0) {
                alert('请输入手机号码');
                return;
            }
            
            if (!validateSenderId(senderId)) {
                alert('发件人ID格式错误：必须是3-11位字母或数字');
                return;
            }
            
            const resultBox = document.getElementById('resultBox');
            resultBox.style.display = 'block';
            resultBox.innerHTML = '<div class="alert alert-info"><i class="bi bi-hourglass-split"></i> 正在提交...</div>';
            
            const requestBody = {
                phones: phones,
                content: content
            };
            
            if (senderId) {
                requestBody.sender_id = senderId;
            }
            
            try {
                const result = await apiPost('/sms/send', requestBody);
                
                if (result.code === 0) {
                    resultBox.innerHTML = `
                        <div class="alert alert-success">
                            <h5><i class="bi bi-check-circle"></i> 提交成功!</h5>
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>任务ID:</strong> ${result.data.task_id}</p>
                                    <p class="mb-1"><strong>号码数量:</strong> ${result.data.total_phones}</p>
                                    <p class="mb-1"><strong>短信条数:</strong> ${result.data.sms_count}</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>总费用:</strong> ${result.data.total_cost} 元</p>
                                    <p class="mb-1"><strong>余额:</strong> ${result.data.balance_after} 元</p>
                                    ${result.data.sender_id ? '<p class="mb-1"><strong>发件人ID:</strong> ' + result.data.sender_id + '</p>' : ''}
                                </div>
                            </div>
                        </div>
                    `;
                    document.getElementById('phones').value = '';
                    document.getElementById('content').value = '';
                    document.getElementById('sender_id').value = '';
                    document.getElementById('phoneFile').value = '';
                    document.getElementById('phoneFileName').textContent = '';
                    updatePreview();
                } else {
                    resultBox.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> 提交失败: ' + result.message + '</div>';
                }
            } catch (e) {
                resultBox.innerHTML = '<div class="alert alert-danger"><i class="bi bi-x-circle"></i> 请求失败: ' + e.message + '</div>';
            }
        });
        
        updatePreview();
    </script>
</body>
</html>
