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
    <link rel="stylesheet" href="../css/style.css">
    <script>window.SESSION_TOKEN = '<?php echo $_SESSION["token"] ?? ""; ?>';</script>
</head>
<body>
    <div class="header">
        <h2>短信平台</h2>
        <div class="user-info">
            <span>欢迎, <?php echo htmlspecialchars($userInfo['username']); ?></span>
            <span>余额: <strong><?php echo $userInfo['balance']; ?></strong></span>
            <a href="../api/logout.php" class="logout">退出</a>
        </div>
    </div>
    
    <div class="layout">
        <div class="sidebar">
            <ul>
                <li><a href="dashboard.php">仪表盘</a></li>
                <li><a href="send_sms.php" class="active">发送短信</a></li>
                <li><a href="records.php">短信记录</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="page-header">
                <h1>发送短信</h1>
            </div>
            
            <div class="send-form">
                <form id="sendForm">
                    <div class="form-group">
                        <label>手机号码 (每行一个或用逗号分隔)</label>
                        <textarea class="phones-input" id="phones" placeholder="+8613800000000&#10;+8613900001111&#10;或: +8613800000000, +8613900001111" required></textarea>
                        <div style="margin-top: 8px;">
                            <label for="phoneFile" class="btn btn-secondary" style="display: inline-block; padding: 8px 16px; cursor: pointer;">
                                📁 从TXT文件导入号码
                            </label>
                            <input type="file" id="phoneFile" accept=".txt" style="display: none;">
                            <span id="phoneFileName" style="margin-left: 10px; color: #666;"></span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>发件人ID (3-11位字母或数字，留空则使用系统默认)</label>
                        <input type="text" id="sender_id" placeholder="如: MyCompany" maxlength="11" pattern="[A-Za-z0-9]{3,11}">
                        <div style="margin-top: 5px; color: #666; font-size: 12px;">选填，发件人ID将显示在接收方手机上</div>
                    </div>
                    
                    <div class="form-group">
                        <label>短信内容</label>
                        <textarea id="content" placeholder="请输入短信内容" required maxlength="500"></textarea>
                        <div style="margin-top: 5px; color: #666; font-size: 12px;">
                            <span id="charCount">0</span> / 500 字符
                            <span id="smsCount" style="margin-left: 15px;"></span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>费用预览</label>
                        <div id="costPreview" style="padding: 15px; background: #f5f5f5; border-radius: 5px;">
                            <div>号码数量: <span id="phoneCount">0</span></div>
                            <div>短信条数: <span id="totalSms">0</span></div>
                            <div>单价: <span><?php echo $userInfo['price']; ?></span> 元/条</div>
                            <div>发件人ID: <span id="senderIdDisplay">未指定</span></div>
                            <div style="font-size: 18px; font-weight: bold; margin-top: 10px;">
                                总费用: <span id="totalCost">0</span> 元
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">提交发送</button>
                </form>
                
                <div id="resultBox" class="result-box"></div>
            </div>
        </div>
    </div>
    
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
            if (len <= 160) return 1;
            if (len <= 306) return 2;
            if (len <= 459) return 3;
            if (len <= 612) return 4;
            return Math.ceil(len / 153);
        }
        
        function validateSenderId(senderId) {
            if (!senderId) return true;
            const regex = /^[A-Za-z0-9]{3,11}$/;
            return regex.test(senderId);
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
            document.getElementById('smsCount').textContent = smsCount > 1 ? `(将分成 ${smsCount} 条发送)` : '';
            document.getElementById('senderIdDisplay').textContent = senderId || '未指定';
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
            resultBox.className = 'result-box';
            resultBox.innerHTML = '<div style="color: blue;">正在提交...</div>';
            resultBox.classList.add('show');
            
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
                            <strong>提交成功!</strong><br>
                            任务ID: ${result.data.task_id}<br>
                            号码数量: ${result.data.total_phones}<br>
                            短信条数: ${result.data.sms_count}<br>
                            ${result.data.sender_id ? '发件人ID: ' + result.data.sender_id + '<br>' : ''}
                            总费用: ${result.data.total_cost} 元<br>
                            余额: ${result.data.balance_after} 元
                        </div>
                    `;
                    document.getElementById('phones').value = '';
                    document.getElementById('content').value = '';
                    document.getElementById('sender_id').value = '';
                    updatePreview();
                } else {
                    resultBox.innerHTML = '<div class="alert alert-error">提交失败: ' + result.message + '</div>';
                }
            } catch (e) {
                resultBox.innerHTML = '<div class="alert alert-error">请求失败: ' + e.message + '</div>';
            }
        });
    </script>
</body>
</html>
