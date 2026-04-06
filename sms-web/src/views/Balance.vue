<template>
  <div class="balance">
    <el-card>
      <template #header>
        <span>Account Balance</span>
      </template>
      <div class="balance-display">
        <span class="label">Current Balance</span>
        <span class="value">${{ balance.toFixed(2) }}</span>
      </div>
    </el-card>
    
    <el-card style="margin-top: 20px">
      <template #header>
        <span>Recent Transactions</span>
      </template>
      <el-table :data="transactions" stripe>
        <el-table-column prop="amount" label="Amount">
          <template #default="{ row }">
            <span :class="row.amount > 0 ? 'positive' : 'negative'">
              {{ row.amount > 0 ? '+' : '' }}${{ row.amount.toFixed(2) }}
            </span>
          </template>
        </el-table-column>
        <el-table-column prop="description" label="Description" />
        <el-table-column prop="createdAt" label="Date">
          <template #default="{ row }">
            {{ new Date(row.createdAt).toLocaleString() }}
          </template>
        </el-table-column>
      </el-table>
    </el-card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useUserStore } from '../stores/user'
import { userApi } from '../api'

const userStore = useUserStore()
const balance = ref(0)
const transactions = ref([])

onMounted(async () => {
  try {
    const res = await userApi.getBalance(userStore.userInfo.id)
    balance.value = res.balance
    transactions.value = [
      { amount: res.balance, description: 'Current Balance', createdAt: new Date() }
    ]
  } catch (error) {
    console.error(error)
  }
})
</script>

<style scoped>
.balance-display {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 40px;
}
.balance-display .label {
  font-size: 14px;
  color: #909399;
}
.balance-display .value {
  font-size: 48px;
  font-weight: bold;
  color: #409eff;
  margin-top: 10px;
}
.positive {
  color: #67c23a;
}
.negative {
  color: #f56c6c;
}
</style>
