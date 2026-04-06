<template>
  <div class="sms-history">
    <el-card>
      <template #header>
        <div class="header">
          <span>Message History</span>
          <el-button type="primary" @click="exportData">Export CSV</el-button>
        </div>
      </template>
      
      <el-table :data="messages" stripe style="width: 100%">
        <el-table-column prop="externalId" label="External ID" width="150" />
        <el-table-column prop="receiverNumber" label="To" />
        <el-table-column prop="totalSegments" label="Segments" width="80" />
        <el-table-column prop="totalPrice" label="Price" width="80">
          <template #default="{ row }">${{ row.totalPrice }}</template>
        </el-table-column>
        <el-table-column prop="status" label="Status" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">{{ row.status }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="createdAt" label="Created" width="180">
          <template #default="{ row }">
            {{ new Date(row.createdAt).toLocaleString() }}
          </template>
        </el-table-column>
        <el-table-column label="Actions" width="150">
          <template #default="{ row }">
            <el-button size="small" @click="checkStatus(row)">Check</el-button>
            <el-button 
              v-if="row.status === 'Failed' || row.status === 'NotSent'" 
              size="small" 
              type="warning"
              @click="resubmit(row)"
            >
              Resubmit
            </el-button>
          </template>
        </el-table-column>
      </el-table>
      
      <el-pagination
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        :total="total"
        layout="total, prev, pager, next"
        style="margin-top: 20px"
        @current-change="fetchData"
      />
    </el-card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { smsApi } from '../api'

const messages = ref([])
const currentPage = ref(1)
const pageSize = ref(20)
const total = ref(0)

const getStatusType = (status) => {
  const types = {
    'Delivered': 'success',
    'Sent': 'primary',
    'Failed': 'danger',
    'Pending': 'warning',
    'Queued': 'info',
    'NotSent': 'danger'
  }
  return types[status] || ''
}

const fetchData = async () => {
  try {
    const data = await smsApi.getHistory(currentPage.value, pageSize.value)
    messages.value = data
    total.value = data.length
  } catch (error) {
    console.error(error)
  }
}

const checkStatus = async (row) => {
  try {
    const status = await smsApi.getStatus(row.messageId || row.id)
    ElMessageBox.alert(`Current status: ${status.status}`, 'Message Status')
  } catch (error) {
    console.error(error)
  }
}

const resubmit = async (row) => {
  try {
    await smsApi.resubmit([row.id])
    ElMessage.success('Message resubmitted')
    fetchData()
  } catch (error) {
    console.error(error)
  }
}

const exportData = () => {
  const csv = [
    ['External ID', 'To', 'Segments', 'Price', 'Status', 'Created'].join(','),
    ...messages.value.map(m => [
      m.externalId,
      m.receiverNumber,
      m.totalSegments,
      m.totalPrice,
      m.status,
      new Date(m.createdAt).toLocaleString()
    ].join(','))
  ].join('\n')
  
  const blob = new Blob([csv], { type: 'text/csv' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `sms_history_${Date.now()}.csv`
  a.click()
}

onMounted(fetchData)
</script>

<style scoped>
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
</style>
