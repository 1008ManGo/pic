<template>
  <div class="alerts">
    <el-card>
      <template #header>
        <span>System Alerts</span>
      </template>
      
      <el-table :data="alerts" stripe style="width: 100%">
        <el-table-column prop="type" label="Type" width="150">
          <template #default="{ row }">
            <el-tag :type="getTypeColor(row.type)">{{ row.type }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="message" label="Message" />
        <el-table-column prop="status" label="Status" width="120">
          <template #default="{ row }">
            <el-tag :type="getStatusColor(row.status)">{{ row.status }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="createdAt" label="Created">
          <template #default="{ row }">
            {{ new Date(row.createdAt).toLocaleString() }}
          </template>
        </el-table-column>
        <el-table-column label="Actions" width="200">
          <template #default="{ row }">
            <el-button 
              v-if="row.status === 'Active'" 
              size="small" 
              @click="acknowledge(row.id)"
            >
              Acknowledge
            </el-button>
            <el-button 
              v-if="row.status !== 'Resolved'" 
              size="small" 
              type="success"
              @click="resolve(row.id)"
            >
              Resolve
            </el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { adminApi } from '../../api'
import { ElMessage } from 'element-plus'

const alerts = ref([])

const getTypeColor = (type) => {
  const colors = {
    'ChannelOffline': 'danger',
    'HighFailureRate': 'warning',
    'LowBalance': 'warning',
    'QueueOverflow': 'danger',
    'TpsExceeded': 'danger'
  }
  return colors[type] || 'info'
}

const getStatusColor = (status) => {
  const colors = {
    'Active': 'danger',
    'Acknowledged': 'warning',
    'Resolved': 'success'
  }
  return colors[status] || 'info'
}

const fetchAlerts = async () => {
  try {
    alerts.value = await adminApi.getAlerts()
  } catch (error) {
    console.error(error)
  }
}

const acknowledge = async (id) => {
  try {
    await adminApi.acknowledgeAlert(id)
    ElMessage.success('Alert acknowledged')
    fetchAlerts()
  } catch (error) {
    console.error(error)
  }
}

const resolve = async (id) => {
  try {
    await adminApi.resolveAlert(id)
    ElMessage.success('Alert resolved')
    fetchAlerts()
  } catch (error) {
    console.error(error)
  }
}

onMounted(fetchAlerts)
</script>
