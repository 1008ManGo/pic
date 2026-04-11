<?php
session_start();

if (!isset($_SESSION['token'])) {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>API Debug - 短信平台</title>
    <script>
        window.SESSION_TOKEN = '<?php echo $_SESSION["token"]; ?>';
    </script>
</head>
<body>
    <h1>API Debug Test</h1>
    <div id="token-info"></div>
    <div id="test-result"></div>
    
    <script>
        const API_BASE = '/api';
        
        document.getElementById('token-info').innerHTML = 
            'Token: <pre>' + window.SESSION_TOKEN + '</pre>';
        
        async function testApi() {
            const resultDiv = document.getElementById('test-result');
            resultDiv.innerHTML = 'Testing...';
            
            const token = window.SESSION_TOKEN;
            console.log('Token:', token);
            
            try {
                const response = await fetch(API_BASE + '/admin/channels', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + token
                    }
                });
                
                const data = await response.json();
                console.log('Response:', data);
                
                resultDiv.innerHTML = '<h3>Result:</h3><pre>' + 
                    'Status: ' + response.status + '\n' +
                    'Data: ' + JSON.stringify(data, null, 2) + '</pre>';
            } catch (e) {
                resultDiv.innerHTML = '<h3>Error:</h3><pre>' + e.message + '</pre>';
            }
        }
        
        testApi();
    </script>
</body>
</html>
