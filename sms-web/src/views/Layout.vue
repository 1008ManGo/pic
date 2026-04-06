<template>
  <el-container class="layout-container">
    <el-aside width="200px">
      <div class="logo">SMS Platform</div>
      <el-menu :default-active="$route.path" router>
        <el-menu-item index="/dashboard">
          <el-icon><Odometer /></el-icon>
          <span>Dashboard</span>
        </el-menu-item>
        <el-menu-item index="/sms/send">
          <el-icon><Message /></el-icon>
          <span>Send SMS</span>
        </el-menu-item>
        <el-menu-item index="/sms/batch">
          <el-icon><DocumentCopy /></el-icon>
          <span>Batch Send</span>
        </el-menu-item>
        <el-menu-item index="/sms/history">
          <el-icon><List /></el-icon>
          <span>History</span>
        </el-menu-item>
        <el-menu-item index="/balance">
          <el-icon><Wallet /></el-icon>
          <span>Balance</span>
        </el-menu-item>
        <el-menu-item index="/apikeys">
          <el-icon><Key /></el-icon>
          <span>API Keys</span>
        </el-menu-item>
        <el-sub-menu v-if="isAdmin" index="admin">
          <template #title>
            <el-icon><Setting /></el-icon>
            <span>Admin</span>
          </template>
          <el-menu-item index="/admin/channels">Channels</el-menu-item>
          <el-menu-item index="/admin/users">Users</el-menu-item>
          <el-menu-item index="/admin/pricing">Pricing</el-menu-item>
          <el-menu-item index="/admin/recharge">Recharge</el-menu-item>
          <el-menu-item index="/admin/senderids">Sender IDs</el-menu-item>
          <el-menu-item index="/admin/alerts">Alerts</el-menu-item>
        </el-sub-menu>
      </el-menu>
    </el-aside>
    <el-container>
      <el-header>
        <div class="header-content">
          <h2>{{ pageTitle }}</h2>
          <div class="user-info">
            <span>{{ userStore.userInfo.username }}</span>
            <el-dropdown @command="handleCommand">
              <el-avatar :size="32">{{ userStore.userInfo.username?.[0] || 'U' }}</el-avatar>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item command="logout">Logout</el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
          </div>
        </div>
      </el-header>
      <el-main>
        <router-view />
      </el-main>
    </el-container>
  </el-container>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useUserStore } from '../stores/user'
import { Odometer, Message, DocumentCopy, List, Wallet, Key, Setting } from '@element-plus/icons-vue'

const router = useRouter()
const route = useRoute()
const userStore = useUserStore()

const isAdmin = computed(() => userStore.userInfo?.role === 'Admin')

const pageTitle = computed(() => {
  const titles = {
    '/dashboard': 'Dashboard',
    '/sms/send': 'Send SMS',
    '/sms/batch': 'Batch Send',
    '/sms/history': 'Message History',
    '/balance': 'Balance',
    '/apikeys': 'API Keys',
    '/admin/channels': 'Channel Management',
    '/admin/users': 'User Management',
    '/admin/pricing': 'Pricing Configuration',
    '/admin/recharge': 'Recharge',
    '/admin/senderids': 'Sender ID Management',
    '/admin/alerts': 'Alerts'
  }
  return titles[route.path] || 'SMS Platform'
})

const handleCommand = (command) => {
  if (command === 'logout') {
    userStore.logout()
    router.push('/login')
  }
}
</script>

<style scoped>
.layout-container {
  height: 100vh;
}
.el-aside {
  background: #001529;
  color: white;
}
.logo {
  height: 60px;
  line-height: 60px;
  text-align: center;
  font-size: 18px;
  font-weight: bold;
  color: #fff;
  background: #002140;
}
.el-header {
  background: #fff;
  box-shadow: 0 1px 4px rgba(0,21,41,.08);
  display: flex;
  align-items: center;
}
.header-content {
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.user-info {
  display: flex;
  align-items: center;
  gap: 10px;
}
</style>
