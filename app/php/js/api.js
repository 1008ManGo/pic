const API_BASE = '/api';

function getToken() {
    const token = window.SESSION_TOKEN || '';
    console.log('[DEBUG] getToken() called, token:', token ? token.substring(0, 20) + '...' : 'EMPTY/UNDEFINED');
    return token;
}

async function apiRequest(endpoint, options = {}) {
    const token = getToken();
    
    const headers = {
        'Content-Type': 'application/json'
    };
    
    if (token) {
        headers['Authorization'] = 'Bearer ' + token;
        console.log('[DEBUG] Authorization header set:', headers['Authorization'].substring(0, 30) + '...');
    } else {
        console.log('[DEBUG] No token, Authorization header NOT set');
    }
    
    console.log('[DEBUG] Making request to:', API_BASE + endpoint);
    
    const response = await fetch(API_BASE + endpoint, {
        ...options,
        headers: headers
    });
    
    console.log('[DEBUG] Response status:', response.status);
    
    const data = await response.json();
    
    console.log('[DEBUG] Response data:', JSON.stringify(data).substring(0, 200));
    
    if (data.code === 3003 || data.code === 3002) {
        console.log('[DEBUG] Token expired error detected');
        alert('登录已过期，请重新登录');
        window.location.href = '../index.php';
        throw new Error('Unauthorized');
    }
    
    if (data.code === 5000) {
        console.log('[DEBUG] Server error detected:', data.message);
        throw new Error(data.message || '服务器内部错误');
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
