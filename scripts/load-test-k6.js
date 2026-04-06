import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const errorRate = new Rate('errors');

export const options = {
  stages: [
    { duration: '30s', target: 20 },
    { duration: '1m', target: 50 },
    { duration: '30s', target: 100 },
    { duration: '1m', target: 200 },
    { duration: '30s', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'],
    http_req_failed: ['rate<0.01'],
    errors: ['rate<0.1'],
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:5000';

export function setup() {
  const registerRes = http.post(`${BASE_URL}/api/user/register`, 
    JSON.stringify({
      username: `k6test_${Date.now()}`,
      email: `k6test_${Date.now()}@test.com`,
      password: 'Test123456'
    }),
    { headers: { 'Content-Type': 'application/json' } }
  );
  
  const loginRes = http.post(`${BASE_URL}/api/user/login`,
    JSON.stringify({
      username: 'testuser',
      password: 'Test123456'
    }),
    { headers: { 'Content-Type': 'application/json' } }
  );
  
  let token = '';
  if (loginRes.status === 200) {
    token = loginRes.json('accessToken');
  }
  
  return { token };
}

export default function(data) {
  const headers = {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${data.token}`,
    'X-User-Id': 'test-user-id'
  };

  const encodingRes = http.post(`${BASE_URL}/api/sms/encode`,
    JSON.stringify('Hello World test message'),
    { headers }
  );
  
  check(encodingRes, {
    'encoding endpoint status 200': (r) => r.status === 200,
    'encoding endpoint returns data': (r) => r.json('encoding') !== undefined,
  }) || errorRate.add(1);

  sleep(0.1);

  const statusRes = http.get(`${BASE_URL}/api/sms/status/test-id`,
    { headers }
  );
  
  check(statusRes, {
    'status endpoint works': (r) => r.status === 200 || r.status === 404,
  }) || errorRate.add(1);

  sleep(0.5);
}

export function handleSummary(data) {
  return {
    'stdout': textSummary(data, { indent: ' ', enableColors: true }),
    'summary.json': JSON.stringify(data, null, 2),
  };
}

function textSummary(data, options) {
  const indent = options.indent || '';
  let summary = `${indent}SMS Platform Load Test Results\n`;
  summary += `${indent============================\n\n`;
  
  if (data.metrics.http_req_duration) {
    const duration = data.metrics.http_req_duration;
    summary += `${indent}Request Duration:\n`;
    summary += `${indent}  avg: ${(duration.values.avg / 1000).toFixed(2)}ms\n`;
    summary += `${indent}  p95: ${(duration.values['p(95)'] / 1000).toFixed(2)}ms\n`;
    summary += `${indent}  max: ${(duration.values.max / 1000).toFixed(2)}ms\n\n`;
  }
  
  if (data.metrics.http_reqs) {
    summary += `${indent}Total Requests: ${data.metrics.http_reqs.values.count}\n`;
    summary += `${indent}Request Rate: ${data.metrics.http_reqs.values.rate.toFixed(2)}/s\n\n`;
  }
  
  if (data.metrics.errors) {
    summary += `${indent}Error Rate: ${(data.metrics.errors.values.rate * 100).toFixed(2)}%\n`;
  }
  
  return summary;
}
