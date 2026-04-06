<template>
  <div class="apikeys">
    <el-card>
      <template #header>
        <div class="header">
          <span>API Keys</span>
          <el-button type="primary" @click="showCreateDialog = true">Create New Key</el-button>
        </div>
      </template>
      
      <el-alert
        title="API Key Usage"
        type="info"
        :closable="false"
        style="margin-bottom: 20px"
      >
        <template #default>
          <p>Use your API Key to authenticate API requests. Include it in the X-Api-Key header.</p>
        </template>
      </el-alert>
      
      <el-table :data="apiKeys" stripe>
        <el-table-column prop="name" label="Name" />
        <el-table-column prop="key" label="API Key" width="300">
          <template #default="{ row }">
            <code>{{ row.key }}</code>
          </template>
        </el-table-column>
        <el-table-column prop="isActive" label="Status">
          <template #default="{ row }">
            <el-tag :type="row.isActive ? 'success' : 'danger'">
              {{ row.isActive ? 'Active' : 'Inactive' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="createdAt" label="Created">
          <template #default="{ row }">
            {{ new Date(row.createdAt).toLocaleString() }}
          </template>
        </el-table-column>
        <el-table-column prop="lastUsedAt" label="Last Used">
          <template #default="{ row }">
            {{ row.lastUsedAt ? new Date(row.lastUsedAt).toLocaleString() : 'Never' }}
          </template>
        </el-table-column>
        <el-table-column label="Actions">
          <template #default="{ row }">
            <el-button size="small" @click="copyKey(row.key)">Copy</el-button>
            <el-button size="small" type="danger" @click="revokeKey(row.id)">Revoke</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>
    
    <el-dialog v-model="showCreateDialog" title="Create API Key" width="400px">
      <el-form :model="newKeyForm" ref="formRef">
        <el-form-item label="Key Name" prop="name">
          <el-input v-model="newKeyForm.name" placeholder="e.g., Production Key" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showCreateDialog = false">Cancel</el-button>
        <el-button type="primary" @click="createKey">Create</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useUserStore } from '../stores/user'
import { userApi } from '../api'
import { ElMessage } from 'element-plus'

const userStore = useUserStore()
const apiKeys = ref([])
const showCreateDialog = ref(false)
const newKeyForm = reactive({ name: '' })

const fetchKeys = async () => {
  try {
    apiKeys.value = await userApi.getApiKeys(userStore.userInfo.id)
  } catch (error) {
    console.error(error)
  }
}

const createKey = async () => {
  try {
    await userApi.createApiKey(userStore.userInfo.id, newKeyForm.name)
    ElMessage.success('API Key created')
    showCreateDialog.value = false
    newKeyForm.name = ''
    fetchKeys()
  } catch (error) {
    console.error(error)
  }
}

const copyKey = (key) => {
  navigator.clipboard.writeText(key)
  ElMessage.success('Copied to clipboard')
}

const revokeKey = async (id) => {
  try {
    await userApi.revokeApiKey(id)
    ElMessage.success('API Key revoked')
    fetchKeys()
  } catch (error) {
    console.error(error)
  }
}

onMounted(fetchKeys)
</script>

<style scoped>
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
code {
  background: #f5f7fa;
  padding: 4px 8px;
  border-radius: 4px;
}
</style>
