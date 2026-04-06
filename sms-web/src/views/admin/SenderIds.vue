<template>
  <div class="senderids">
    <el-card>
      <template #header>
        <span>Sender ID Management</span>
      </template>
      
      <el-table :data="senderIds" stripe style="width: 100%">
        <el-table-column prop="senderIdValue" label="Sender ID" />
        <el-table-column prop="userId" label="User ID" width="250" />
        <el-table-column prop="isApproved" label="Approved">
          <template #default="{ row }">
            <el-tag :type="row.isApproved ? 'success' : 'warning'">
              {{ row.isApproved ? 'Yes' : 'Pending' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="isDefault" label="Default">
          <template #default="{ row }">
            <el-tag v-if="row.isDefault" type="info">Default</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="createdAt" label="Created">
          <template #default="{ row }">
            {{ new Date(row.createdAt).toLocaleDateString() }}
          </template>
        </el-table-column>
        <el-table-column label="Actions">
          <template #default="{ row }">
            <el-button 
              v-if="!row.isApproved" 
              size="small" 
              type="success"
              @click="approve(row.id)"
            >
              Approve
            </el-button>
            <el-button size="small" type="danger" @click="remove(row.id)">Remove</el-button>
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

const senderIds = ref([])

const fetchSenderIds = async () => {
  try {
    senderIds.value = []
  } catch (error) {
    console.error(error)
  }
}

const approve = async (id) => {
  try {
    await adminApi.approveSenderId(id)
    ElMessage.success('Sender ID approved')
    fetchSenderIds()
  } catch (error) {
    console.error(error)
  }
}

const remove = async (id) => {
  try {
    await adminApi.deleteSenderId(id)
    ElMessage.success('Sender ID removed')
    fetchSenderIds()
  } catch (error) {
    console.error(error)
  }
}

onMounted(fetchSenderIds)
</script>
