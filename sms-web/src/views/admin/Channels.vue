<template>
  <div class="channels">
    <el-card>
      <template #header>
        <div class="header">
          <span>SMPP Channels</span>
          <el-button type="primary" @click="showCreateDialog = true">Add Channel</el-button>
        </div>
      </template>
      
      <el-table :data="channels" stripe style="width: 100%">
        <el-table-column prop="name" label="Name" />
        <el-table-column prop="host" label="Host:Port">
          <template #default="{ row }">{{ row.host }}:{{ row.port }}</template>
        </el-table-column>
        <el-table-column prop="maxTps" label="Max TPS" />
        <el-table-column prop="isOnline" label="Status">
          <template #default="{ row }">
            <el-tag :type="row.isOnline ? 'success' : 'danger'">
              {{ row.isOnline ? 'Online' : 'Offline' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="currentTps" label="Current TPS" />
        <el-table-column prop="queueLength" label="Queue" />
        <el-table-column prop="successRate" label="Success Rate">
          <template #default="{ row }">{{ row.successRate.toFixed(1) }}%</template>
        </el-table-column>
        <el-table-column prop="status" label="Config Status">
          <template #default="{ row }">
            <el-tag :type="row.status === 'Active' ? 'success' : 'info'">{{ row.status }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="Actions" width="200">
          <template #default="{ row }">
            <el-button size="small" @click="editChannel(row)">Edit</el-button>
            <el-button size="small" type="danger" @click="deleteChannel(row.id)">Delete</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>
    
    <el-dialog v-model="showDialog" :title="isEdit ? 'Edit Channel' : 'Add Channel'" width="600px">
      <el-form :model="form" label-width="120px">
        <el-form-item label="Name">
          <el-input v-model="form.name" />
        </el-form-item>
        <el-form-item label="Host">
          <el-input v-model="form.host" placeholder="smpp.example.com" />
        </el-form-item>
        <el-form-item label="Port">
          <el-input-number v-model="form.port" :min="1" :max="65535" />
        </el-form-item>
        <el-form-item label="Username">
          <el-input v-model="form.username" />
        </el-form-item>
        <el-form-item label="Password">
          <el-input v-model="form.password" type="password" show-password />
        </el-form-item>
        <el-form-item label="Max TPS">
          <el-input-number v-model="form.maxTps" :min="1" />
        </el-form-item>
        <el-form-item label="Max Bind Count">
          <el-input-number v-model="form.maxBindCount" :min="1" />
        </el-form-item>
        <el-form-item label="System Type">
          <el-input v-model="form.systemType" placeholder="SMPP" />
        </el-form-item>
        <el-form-item label="Status">
          <el-select v-model="form.status">
            <el-option value="Active" label="Active" />
            <el-option value="Inactive" label="Inactive" />
            <el-option value="Suspended" label="Suspended" />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showDialog = false">Cancel</el-button>
        <el-button type="primary" @click="saveChannel">Save</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { channelApi } from '../../api'
import { ElMessage, ElMessageBox } from 'element-plus'

const channels = ref([])
const showDialog = ref(false)
const showCreateDialog = ref(false)
const isEdit = ref(false)
const form = reactive({
  id: null,
  name: '',
  host: '',
  port: 2775,
  username: '',
  password: '',
  maxTps: 100,
  maxBindCount: 10,
  systemType: 'SMPP',
  status: 'Active'
})

const fetchChannels = async () => {
  try {
    channels.value = await channelApi.getAll()
  } catch (error) {
    console.error(error)
  }
}

const editChannel = (channel) => {
  isEdit.value = true
  Object.assign(form, {
    id: channel.id,
    name: channel.name,
    host: channel.host,
    port: channel.port,
    username: '',
    password: '',
    maxTps: channel.maxTps,
    maxBindCount: channel.maxBindCount,
    systemType: channel.systemType,
    status: channel.status
  })
  showDialog.value = true
}

const saveChannel = async () => {
  try {
    if (isEdit.value) {
      await channelApi.update(form)
      ElMessage.success('Channel updated')
    } else {
      await channelApi.create(form)
      ElMessage.success('Channel created')
    }
    showDialog.value = false
    fetchChannels()
  } catch (error) {
    console.error(error)
  }
}

const deleteChannel = async (id) => {
  try {
    await ElMessageBox.confirm('Delete this channel?', 'Warning', { type: 'warning' })
    await channelApi.delete(id)
    ElMessage.success('Channel deleted')
    fetchChannels()
  } catch (error) {
    console.error(error)
  }
}

onMounted(fetchChannels)
</script>

<style scoped>
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
</style>
