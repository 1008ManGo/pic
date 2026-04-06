<template>
  <div class="pricing">
    <el-card>
      <template #header>
        <span>Country Pricing Configuration</span>
      </template>
      
      <el-table :data="pricingList" stripe style="width: 100%">
        <el-table-column prop="countryCode" label="Country Code" width="120" />
        <el-table-column prop="countryName" label="Country" />
        <el-table-column prop="pricePerSms" label="Price/SMS">
          <template #default="{ row }">${{ row.pricePerSms.toFixed(4) }}</template>
        </el-table-column>
        <el-table-column prop="segmentSize" label="Segment Size" />
        <el-table-column prop="longMessageSegmentSize" label="Long SMS Segment" />
        <el-table-column label="Actions">
          <template #default="{ row }">
            <el-button size="small" @click="editPricing(row)">Edit</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>
    
    <el-dialog v-model="showDialog" title="Edit Pricing" width="400px">
      <el-form :model="form" label-width="130px">
        <el-form-item label="Country">
          <el-input v-model="form.countryName" disabled />
        </el-form-item>
        <el-form-item label="Price per SMS">
          <el-input-number v-model="form.pricePerSms" :min="0" :step="0.0001" :precision="4" />
        </el-form-item>
        <el-form-item label="Segment Size">
          <el-input-number v-model="form.segmentSize" :min="1" />
        </el-form-item>
        <el-form-item label="Long SMS Segment">
          <el-input-number v-model="form.longMessageSegmentSize" :min="1" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showDialog = false">Cancel</el-button>
        <el-button type="primary" @click="savePricing">Save</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { adminApi } from '../../api'
import { ElMessage } from 'element-plus'

const pricingList = ref([])
const showDialog = ref(false)
const form = reactive({
  countryId: null,
  countryName: '',
  pricePerSms: 0.01,
  segmentSize: 160,
  longMessageSegmentSize: 153
})

const fetchPricing = async () => {
  try {
    const countries = await adminApi.getCountries()
    pricingList.value = countries.map(c => ({
      countryId: c.id,
      countryCode: c.code,
      countryName: c.name,
      pricePerSms: 0.01,
      segmentSize: 160,
      longMessageSegmentSize: 153
    }))
  } catch (error) {
    console.error(error)
  }
}

const editPricing = async (row) => {
  try {
    const pricing = await adminApi.getPricing(row.countryId)
    Object.assign(form, {
      countryId: row.countryId,
      countryName: row.countryName,
      pricePerSms: pricing.pricePerSms,
      segmentSize: pricing.segmentSize,
      longMessageSegmentSize: pricing.longMessageSegmentSize
    })
  } catch {
    Object.assign(form, {
      countryId: row.countryId,
      countryName: row.countryName,
      pricePerSms: 0.01,
      segmentSize: 160,
      longMessageSegmentSize: 153
    })
  }
  showDialog.value = true
}

const savePricing = async () => {
  try {
    await adminApi.setPricing({
      countryId: form.countryId,
      pricePerSms: form.pricePerSms,
      segmentSize: form.segmentSize,
      longMessageSegmentSize: form.longMessageSegmentSize
    })
    ElMessage.success('Pricing updated')
    showDialog.value = false
    fetchPricing()
  } catch (error) {
    console.error(error)
  }
}

onMounted(fetchPricing)
</script>
