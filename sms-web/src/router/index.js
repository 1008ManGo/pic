import { createRouter, createWebHistory } from 'vue-router'
import { useUserStore } from '../stores/user'

const routes = [
  {
    path: '/',
    redirect: '/dashboard'
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/Login.vue')
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('../views/Register.vue')
  },
  {
    path: '/',
    component: () => import('../views/Layout.vue'),
    children: [
      {
        path: 'dashboard',
        name: 'Dashboard',
        component: () => import('../views/Dashboard.vue')
      },
      {
        path: 'sms/send',
        name: 'SmsSend',
        component: () => import('../views/SmsSend.vue')
      },
      {
        path: 'sms/batch',
        name: 'SmsBatch',
        component: () => import('../views/SmsBatch.vue')
      },
      {
        path: 'sms/history',
        name: 'SmsHistory',
        component: () => import('../views/SmsHistory.vue')
      },
      {
        path: 'balance',
        name: 'Balance',
        component: () => import('../views/Balance.vue')
      },
      {
        path: 'apikeys',
        name: 'ApiKeys',
        component: () => import('../views/ApiKeys.vue')
      }
    ]
  },
  {
    path: '/admin',
    component: () => import('../views/AdminLayout.vue'),
    children: [
      {
        path: '',
        redirect: '/admin/channels'
      },
      {
        path: 'channels',
        name: 'Channels',
        component: () => import('../views/admin/Channels.vue')
      },
      {
        path: 'users',
        name: 'Users',
        component: () => import('../views/admin/Users.vue')
      },
      {
        path: 'pricing',
        name: 'Pricing',
        component: () => import('../views/admin/Pricing.vue')
      },
      {
        path: 'recharge',
        name: 'Recharge',
        component: () => import('../views/admin/Recharge.vue')
      },
      {
        path: 'alerts',
        name: 'Alerts',
        component: () => import('../views/admin/Alerts.vue')
      },
      {
        path: 'senderids',
        name: 'SenderIds',
        component: () => import('../views/admin/SenderIds.vue')
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const userStore = useUserStore()
  if (to.path !== '/login' && to.path !== '/register' && !userStore.isLoggedIn) {
    next('/login')
  } else {
    next()
  }
})

export default router
