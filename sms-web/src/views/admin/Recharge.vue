<template>
  <div class="recharge">
    <el-card>
      <template #header>
        <span>User Recharge</span>
      </template>
      
      <el-form :model="form" label-width="120px" style="max-width: 500px">
        <el-form-item label="Select User">
          <el-select v-model="form.userId" filterable placeholder="Search user..." @focus="loadUsers">
            <el-option
              v-for="user in userList"
              :key="user.id"
              :label="user.username"
              :value="user.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="Amount">
          <el-input-number v-model="form.amount" :min="0.01" :step="1" :precision="2" />
        </el-form-item>
        <el-form-item label="Payment Method">
          <el-select v-model="form.paymentMethod">
            <el-option value="Bank Transfer" label="Bank Transfer" />
            <el-option value="Credit Card" label="Credit Card" />
            <el-option value="PayPal" label="PayPal" />
            <el-option value="Other" label="Other" />
          </el-select>
        </el-form-item>
        <el-form-item label="Notes">
          <el-input v-model="form.notes" type="textarea" :rows="2" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :loading="loading" @click="handleRecharge">Recharge</el-button>
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { adminApi, userApi } from '../../api'
import { ElMessage } from 'element-plus'

const userList = ref([])
const loading = ref(false)
const form = reactive({
  userId: null,
  amount: 100,
  paymentMethod: 'Bank Transfer',
  notes: ''
})

const loadUsers = async () => {
  try {
    userList.value = await userApi.getUsers(1, 100)
  } catch (error) {
    console.error(error)
  }
}

const handleRecharge = async () => {
  if (!form.userId) {
    ElMessage.warning('Please select a user')
    return
  }
  
  loading.value = true
  try {
    await adminApi.recharge(form)
    ElMessage.success('Recharge successful')
    form.amount = 100
    form.notes = ''
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}
</script>
