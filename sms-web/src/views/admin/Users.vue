<template>
  <div class="users">
    <el-card>
      <template #header>
        <span>User Management</span>
      </template>
      
      <el-table :data="users" stripe style="width: 100%">
        <el-table-column prop="id" label="ID" width="250" />
        <el-table-column prop="username" label="Username" />
        <el-table-column prop="email" label="Email" />
        <el-table-column prop="companyName" label="Company" />
        <el-table-column prop="balance" label="Balance">
          <template #default="{ row }">${{ row.balance.toFixed(2) }}</template>
        </el-table-column>
        <el-table-column prop="status" label="Status">
          <template #default="{ row }">
            <el-tag :type="row.status === 'Active' ? 'success' : 'danger'">
              {{ row.status }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="createdAt" label="Created">
          <template #default="{ row }">
            {{ new Date(row.createdAt).toLocaleDateString() }}
          </template>
        </el-table-column>
        <el-table-column label="Actions">
          <template #default="{ row }">
            <el-button size="small" @click="viewUser(row)">View</el-button>
            <el-button size="small" type="warning" @click="editUser(row)">Edit</el-button>
          </template>
        </el-table-column>
      </el-table>
      
      <el-pagination
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        :total="total"
        layout="total, prev, pager, next"
        style="margin-top: 20px"
        @current-change="fetchUsers"
      />
    </el-card>
    
    <el-dialog v-model="showDialog" title="Edit User" width="500px">
      <el-form :model="form" label-width="120px">
        <el-form-item label="Email">
          <el-input v-model="form.email" />
        </el-form-item>
        <el-form-item label="Company">
          <el-input v-model="form.companyName" />
        </el-form-item>
        <el-form-item label="Status">
          <el-select v-model="form.status">
            <el-option value="Active" label="Active" />
            <el-option value="Disabled" label="Disabled" />
          </el-select>
        </el-form-item>
        <el-form-item label="Allow Custom Sender">
          <el-switch v-model="form.allowCustomSenderId" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showDialog = false">Cancel</el-button>
        <el-button type="primary" @click="saveUser">Save</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { userApi } from '../../api'
import { ElMessage } from 'element-plus'

const users = ref([])
const currentPage = ref(1)
const pageSize = ref(20)
const total = ref(0)
const showDialog = ref(false)
const form = reactive({
  id: null,
  email: '',
  companyName: '',
  status: 'Active',
  allowCustomSenderId: false
})

const fetchUsers = async () => {
  try {
    const data = await userApi.getUsers(currentPage.value, pageSize.value)
    users.value = data
    total.value = data.length || 0
  } catch (error) {
    console.error(error)
  }
}

const viewUser = (user) => {
  ElMessageBox.alert(`
    <p><strong>ID:</strong> ${user.id}</p>
    <p><strong>Username:</strong> ${user.username}</p>
    <p><strong>Email:</strong> ${user.email}</p>
    <p><strong>Balance:</strong> $${user.balance.toFixed(2)}</p>
    <p><strong>Allow Custom Sender:</strong> ${user.allowCustomSenderId ? 'Yes' : 'No'}</p>
    <p><strong>Created:</strong> ${new Date(user.createdAt).toLocaleString()}</p>
  `, 'User Details', { dangerouslyUseHTMLString: true })
}

const editUser = (user) => {
  Object.assign(form, {
    id: user.id,
    email: user.email,
    companyName: user.companyName,
    status: user.status,
    allowCustomSenderId: user.allowCustomSenderId
  })
  showDialog.value = true
}

const saveUser = async () => {
  try {
    ElMessage.success('User updated')
    showDialog.value = false
    fetchUsers()
  } catch (error) {
    console.error(error)
  }
}

onMounted(fetchUsers)
</script>
