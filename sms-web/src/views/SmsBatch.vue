<template>
  <div class="sms-batch">
    <el-card>
      <template #header>
        <span>Batch Send SMS</span>
      </template>
      <el-alert
        title="Batch Upload Instructions"
        type="info"
        :closable="false"
        style="margin-bottom: 20px"
      >
        <template #default>
          <p>Upload a text file (.txt) containing phone numbers. Each line should have one number with country code.</p>
          <p>Maximum 1,000,000 numbers per file.</p>
        </template>
      </el-alert>
      
      <el-form :model="form" :rules="rules" ref="formRef" label-width="120px">
        <el-form-item label="Sender ID" prop="senderId">
          <el-input v-model="form.senderId" placeholder="Enter sender ID" />
        </el-form-item>
        
        <el-form-item label="Upload File" prop="fileContent">
          <el-upload
            ref="uploadRef"
            :auto-upload="false"
            :limit="1"
            accept=".txt"
            :on-change="handleFileChange"
            :on-remove="handleFileRemove"
          >
            <el-button>Select File</el-button>
            <template #tip>
              <div class="el-upload__tip">.txt file with one phone number per line</div>
            </template>
          </el-upload>
        </el-form-item>
        
        <el-form-item label="Numbers Count">
          <el-tag type="info">{{ numberCount }} numbers</el-tag>
        </el-form-item>
        
        <el-form-item label="Content" prop="content">
          <el-input 
            v-model="form.content" 
            type="textarea" 
            :rows="4" 
            placeholder="Enter message content"
          />
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
          <el-button type="primary" :loading="loading" :disabled="!form.fileContent" @click="handleSubmit">
            Submit Batch Job
          </el-button>
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { ElMessage } from 'element-plus'
import { smsApi } from '../api'

const formRef = ref()
const uploadRef = ref()
const loading = ref(false)
const numberCount = ref(0)

const form = reactive({
  senderId: '',
  fileContent: '',
  content: '',
  priority: 1,
  appendRandomChars: false
})

const rules = {
  senderId: [{ required: true, message: 'Please enter sender ID' }],
  fileContent: [{ required: true, message: 'Please upload a file' }],
  content: [{ required: true, message: 'Please enter content' }]
}

const handleFileChange = (file) => {
  const reader = new FileReader()
  reader.onload = (e) => {
    form.fileContent = e.target.result
    numberCount.value = form.fileContent.split('\n').filter(n => n.trim()).length
  }
  reader.readAsText(file.raw)
}

const handleFileRemove = () => {
  form.fileContent = ''
  numberCount.value = 0
}

const handleSubmit = async () => {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return
  
  loading.value = true
  try {
    await smsApi.submitBatch({
      senderId: form.senderId,
      fileContent: form.fileContent,
      content: form.content,
      priority: form.priority,
      appendRandomChars: form.appendRandomChars
    })
    ElMessage.success('Batch job submitted successfully')
    formRef.value.resetFields()
    numberCount.value = 0
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}
</script>
