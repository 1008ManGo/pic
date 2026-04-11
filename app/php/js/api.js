const API_BASE = 'http://localhost:8080/api';

function getToken() {
    return localStorage.getItem('token') || sessionStorage.getItem('token') || '<?php echo $_SESSION["token"] ?? ""; ?>';
}

async function apiRequest(endpoint, options = {}) {
    const token = getToken();
    
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json',
            ...(token && { 'Authorization': 'Bearer ' + token })
        }
    };
    
    const response = await fetch(API_BASE + endpoint, {
        ...defaultOptions,
        ...options,
        headers: {
            ...defaultOptions.headers,
            ...(options.headers || {})
        }
    });
    
    const data = await response.json();
    
    if (data.code === 3003 || data.code === 3002) {
        alert('登录已过期，请重新登录');
        window.location.href = '../index.php';
        throw new Error('Unauthorized');
    }
    
    return data;
}

async function apiGet(endpoint) {
    return apiRequest(endpoint, { method: 'GET' });
}

async function apiPost(endpoint, body) {
    return apiRequest(endpoint, {
        method: 'POST',
        body: JSON.stringify(body)
    });
}

async function apiPut(endpoint, body) {
    return apiRequest(endpoint, {
        method: 'PUT',
        body: JSON.stringify(body)
    });
}

async function apiDelete(endpoint) {
    return apiRequest(endpoint, { method: 'DELETE' });
}
