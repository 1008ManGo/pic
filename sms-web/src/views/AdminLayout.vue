<template>
  <el-container class="layout-container">
    <el-aside width="200px">
      <div class="logo">SMS Admin</div>
      <el-menu :default-active="$route.path" router>
        <el-menu-item index="/admin/channels">
          <el-icon><Connection /></el-icon>
          <span>Channels</span>
        </el-menu-item>
        <el-menu-item index="/admin/users">
          <el-icon><User /></el-icon>
          <span>Users</span>
        </el-menu-item>
        <el-menu-item index="/admin/pricing">
          <el-icon><PriceTag /></el-icon>
          <span>Pricing</span>
        </el-menu-item>
        <el-menu-item index="/admin/recharge">
          <el-icon><Coin /></el-icon>
          <span>Recharge</span>
        </el-menu-item>
        <el-menu-item index="/admin/senderids">
          <el-icon><Stamp /></el-icon>
          <span>Sender IDs</span>
        </el-menu-item>
        <el-menu-item index="/admin/alerts">
          <el-icon><Bell /></el-icon>
          <span>Alerts</span>
        </el-menu-item>
        <el-menu-item index="/dashboard">
          <el-icon><HomeFilled /></el-icon>
          <span>User Panel</span>
        </el-menu-item>
      </el-menu>
    </el-aside>
    <el-container>
      <el-header>
        <div class="header-content">
          <h2>Admin Panel - {{ pageTitle }}</h2>
          <div class="user-info">
            <el-tag type="danger">Admin</el-tag>
            <span>{{ userStore.userInfo.username }}</span>
            <el-button @click="userStore.logout(); router.push('/login')">Logout</el-button>
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
import { Connection, User, PriceTag, Coin, Stamp, Bell, HomeFilled } from '@element-plus/icons-vue'

const router = useRouter()
const route = useRoute()
const userStore = useUserStore()

const pageTitle = computed(() => {
  const titles = {
    '/admin/channels': 'Channel Management',
    '/admin/users': 'User Management',
    '/admin/pricing': 'Pricing Configuration',
    '/admin/recharge': 'Recharge Management',
    '/admin/senderids': 'Sender ID Management',
    '/admin/alerts': 'System Alerts'
  }
  return titles[route.path] || 'Admin'
})
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
  background: #a00000;
}
.el-header {
  background: #fff;
  box-shadow: 0 1px 4px rgba(0,21,41,.08);
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
