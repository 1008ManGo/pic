<template>
  <div class="sms-send">
    <el-card>
      <template #header>
        <span>Send SMS</span>
      </template>
      <el-form :model="form" :rules="rules" ref="formRef" label-width="120px">
        <el-form-item label="Sender ID" prop="senderId">
          <el-input v-model="form.senderId" placeholder="Enter sender ID" />
        </el-form-item>
        <el-form-item label="Receiver" prop="receiverNumber">
          <el-input v-model="form.receiverNumber" placeholder="+8613800138000" />
        </el-form-item>
        <el-form-item label="Content" prop="content">
          <el-input 
            v-model="form.content" 
            type="textarea" 
            :rows="4" 
            placeholder="Enter message content"
            @input="calculateEncoding"
          />
        </el-form-item>
        <el-form-item label="Encoding Info">
          <el-tag v-if="encodingInfo" :type="encodingInfo.hasExtendedChars ? 'warning' : 'success'">
            {{ encodingInfo.encoding }} | {{ encodingInfo.characterCount }} chars | {{ encodingInfo.totalSegments }} segment(s)
          </el-tag>
        </el-form-item>
        <el-form-item label="Priority">
          <el-select v-model="form.priority" style="width: 100%">
            <el-option :value="0" label="Low" />
            <el-option :value="1" label="Normal" />
            <el-option :value="2" label="High" />
            <el-option :value="3" label="Urgent" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-checkbox v-model="form.appendRandomChars">Append random characters (5 chars)</el-checkbox>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :loading="loading" @click="handleSubmit">
            Send SMS
          </el-button>
        </el-form-item>
      </el-form>
    </el-card>
    
    <el-card v-if="result" style="margin-top: 20px">
      <template #header>
        <span>Result</span>
      </template>
      <el-descriptions :column="2" border>
        <el-descriptions-item label="Message ID">{{ result.messageId }}</el-descriptions-item>
        <el-descriptions-item label="External ID">{{ result.externalId }}</el-descriptions-item>
        <el-descriptions-item label="Segments">{{ result.totalSegments }}</el-descriptions-item>
        <el-descriptions-item label="Total Price">${{ result.totalPrice }}</el-descriptions-item>
        <el-descriptions-item label="Status">
          <el-tag :type="result.status === 'Queued' ? 'success' : 'warning'">{{ result.status }}</el-tag>
        </el-descriptions-item>
      </el-descriptions>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useUserStore } from '../stores/user'
import { smsApi } from '../api'
import { ElMessage, ElMessageBox } from 'element-plus'

const userStore = useUserStore()
const formRef = ref()
const loading = ref(false)
const encodingInfo = ref(null)
const result = ref(null)

const form = reactive({
  senderId: '',
  receiverNumber: '',
  content: '',
  priority: 1,
  appendRandomChars: false
})

const rules = {
  senderId: [{ required: true, message: 'Please enter sender ID' }],
  receiverNumber: [
    { required: true, message: 'Please enter receiver number' },
    { pattern: /^\+?\d+$/, message: 'Invalid phone number format' }
  ],
  content: [{ required: true, message: 'Please enter content' }]
}

const calculateEncoding = async () => {
  if (!form.content) {
    encodingInfo.value = null
    return
  }
  try {
    encodingInfo.value = await smsApi.encode(form.content)
  } catch (error) {
    console.error(error)
  }
}

const handleSubmit = async () => {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return
  
  loading.value = true
  try {
    result.value = await smsApi.submit({
      senderId: form.senderId,
      receiverNumber: form.receiverNumber,
      content: form.content,
      priority: form.priority,
      appendRandomChars: form.appendRandomChars
    })
    ElMessage.success('SMS submitted successfully')
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}
</script>
