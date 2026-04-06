<template>
  <div class="dashboard">
    <el-row :gutter="20">
      <el-col :span="6">
        <el-card>
          <div class="stat-card">
            <div class="stat-icon" style="background: #409eff">
              <el-icon :size="30"><Message /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.totalSent }}</div>
              <div class="stat-label">Total Sent</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card>
          <div class="stat-card">
            <div class="stat-icon" style="background: #67c23a">
              <el-icon :size="30"><SuccessFilled /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.delivered }}</div>
              <div class="stat-label">Delivered</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card>
          <div class="stat-card">
            <div class="stat-icon" style="background: #f56c6c">
              <el-icon :size="30"><CircleCloseFilled /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.failed }}</div>
              <div class="stat-label">Failed</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card>
          <div class="stat-card">
            <div class="stat-icon" style="background: #e6a23c">
              <el-icon :size="30"><Wallet /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">${{ balance }}</div>
              <div class="stat-label">Balance</div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>
    
    <el-row :gutter="20" style="margin-top: 20px">
      <el-col :span="12">
        <el-card>
          <template #header>
            <span>Success Rate</span>
          </template>
          <div ref="successChart" style="height: 250px"></div>
        </el-card>
      </el-col>
      <el-col :span="12">
        <el-card>
          <template #header>
            <span>Recent Messages</span>
          </template>
          <el-table :data="recentMessages" style="width: 100%">
            <el-table-column prop="externalId" label="ID" width="100" />
            <el-table-column prop="receiverNumber" label="To" />
            <el-table-column prop="status" label="Status">
              <template #default="{ row }">
                <el-tag :type="getStatusType(row.status)">{{ row.status }}</el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="createdAt" label="Time">
              <template #default="{ row }">
                {{ new Date(row.createdAt).toLocaleString() }}
              </template>
            </el-table-column>
          </el-table>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import { useUserStore } from '../stores/user'
import { smsApi, userApi } from '../api'
import * as echarts from 'echarts'

const userStore = useUserStore()
const balance = ref(0)
const successChart = ref(null)
const recentMessages = ref([])

const stats = reactive({
  totalSent: 0,
  delivered: 0,
  failed: 0
})

const getStatusType = (status) => {
  const types = {
    'Delivered': 'success',
    'Sent': 'primary',
    'Failed': 'danger',
    'Pending': 'warning',
    'Queued': 'info'
  }
  return types[status] || ''
}

onMounted(async () => {
  try {
    const res = await userApi.getBalance(userStore.userInfo.id)
    balance.value = res.balance
    
    const history = await smsApi.getHistory(1, 10)
    recentMessages.value = history
    
    stats.totalSent = history.length
    stats.delivered = history.filter(m => m.status === 'Delivered').length
    stats.failed = history.filter(m => m.status === 'Failed').length
    
    initChart()
  } catch (error) {
    console.error(error)
  }
})

const initChart = () => {
  if (!successChart.value) return
  const chart = echarts.init(successChart.value)
  const option = {
    tooltip: { trigger: 'item' },
    series: [{
      type: 'pie',
      radius: ['40%', '70%'],
      data: [
        { value: stats.delivered, name: 'Delivered', itemStyle: { color: '#67c23a' } },
        { value: stats.failed, name: 'Failed', itemStyle: { color: '#f56c6c' } },
        { value: stats.totalSent - stats.delivered - stats.failed, name: 'Pending', itemStyle: { color: '#e6a23c' } }
      ]
    }]
  }
  chart.setOption(option)
}
</script>

<style scoped>
.stat-card {
  display: flex;
  align-items: center;
  gap: 20px;
}
.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}
.stat-info {
  flex: 1;
}
.stat-value {
  font-size: 28px;
  font-weight: bold;
}
.stat-label {
  color: #909399;
  font-size: 14px;
}
</style>
