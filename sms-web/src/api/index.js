import axios from 'axios'
import { ElMessage } from 'element-plus'

const api = axios.create({
  baseURL: '/api',
  timeout: 30000
})

api.interceptors.request.use(config => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

api.interceptors.response.use(
  response => response.data,
  error => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token')
      window.location.href = '/login'
    }
    ElMessage.error(error.response?.data?.message || 'Request failed')
    return Promise.reject(error)
  }
)

export const userApi = {
  login: (data) => api.post('/user/login', data),
  register: (data) => api.post('/user/register', data),
  getUser: (id) => api.get(`/user/${id}`),
  getUsers: (page, pageSize) => api.get('/user', { params: { page, pageSize } }),
  getBalance: (userId) => api.get(`/user/${userId}/balance`),
  createApiKey: (userId, name) => api.post(`/user/${userId}/apikeys`, null, { params: { name } }),
  getApiKeys: (userId) => api.get(`/user/${userId}/apikeys`)
}

export const smsApi = {
  submit: (data) => api.post('/sms/submit', data),
  submitBatch: (data) => api.post('/sms/submit/batch', data),
  getStatus: (messageId) => api.get(`/sms/status/${messageId}`),
  getHistory: (page, pageSize) => api.get('/sms/history', { params: { page, pageSize } }),
  resubmit: (messageIds) => api.post('/sms/resubmit', messageIds),
  encode: (content) => api.post('/sms/encode', content)
}

export const channelApi = {
  getAll: () => api.get('/channel'),
  getActive: () => api.get('/channel/active'),
  get: (id) => api.get(`/channel/${id}`),
  create: (data) => api.post('/channel', data),
  update: (data) => api.put('/channel', data),
  delete: (id) => api.delete(`/channel/${id}`),
  getStatuses: () => api.get('/channel/statuses')
}

export const adminApi = {
  getCountries: () => api.get('/admin/countries'),
  getPricing: (countryId, userId) => api.get('/admin/pricing/' + countryId, { params: { userId } }),
  setPricing: (data) => api.post('/admin/pricing', data),
  getUserPricing: (userId) => api.get(`/admin/user/${userId}/pricing`),
  recharge: (data) => api.post('/admin/recharge', data),
  getAlerts: () => api.get('/admin/alerts'),
  acknowledgeAlert: (id) => api.put(`/admin/alerts/${id}/acknowledge`),
  resolveAlert: (id) => api.put(`/admin/alerts/${id}/resolve`),
  getSenderIds: (userId) => api.get(`/admin/user/${userId}/senderids`),
  createSenderId: (data) => api.post('/admin/senderid', data),
  approveSenderId: (id) => api.put(`/admin/senderid/${id}/approve`),
  setDefaultSenderId: (id, userId) => api.put(`/admin/senderid/${id}/default`, null, { params: { userId } }),
  deleteSenderId: (id) => api.delete(`/admin/senderid/${id}`)
}

export default api
