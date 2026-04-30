<template>
  <div class="app-container">
    <el-card shadow="never">
      <div slot="header">
        <span>支付通道配置</span>
      </div>

      <el-form
        ref="form"
        :model="form"
        label-width="140px"
        v-loading="loading"
      >
        <el-form-item label="核心路由">
          <el-select v-model="form.payChannel" placeholder="请选择支付通道" style="width: 100%;">
            <el-option
              v-for="item in channelOptions"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            />
          </el-select>
        </el-form-item>

        <template v-if="form.payChannel === 'wxpay'">
          <div class="section-title">微信直连配置</div>

          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="AppID">
                <el-input v-model="form.wxpayAppid" placeholder="请输入微信 AppID" />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="AppSecret">
                <el-input v-model="form.wxpaySecret" placeholder="请输入微信 AppSecret" show-password />
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="MCHID">
                <el-input v-model="form.wxpayMchid" placeholder="请输入微信商户号 MCHID" />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="KeyV2">
                <el-input v-model="form.wxpayKey" placeholder="请输入微信 KeyV2" show-password />
              </el-form-item>
            </el-col>
          </el-row>
        </template>

        <template v-if="form.payChannel === 'epay'">
          <div class="section-title">易支付配置</div>

          <el-form-item label="接口地址">
            <el-input v-model="form.epayApi" placeholder="请输入易支付接口地址" />
          </el-form-item>

          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="商户ID">
                <el-input v-model="form.epayId" placeholder="请输入易支付商户ID" />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="商户密钥">
                <el-input v-model="form.epayKey" placeholder="请输入易支付商户密钥" show-password />
              </el-form-item>
            </el-col>
          </el-row>
        </template>

        <el-form-item>
          <el-button
            type="primary"
            :loading="submitLoading"
            @click="handleSubmit"
          >
            同步并保存
          </el-button>
          <el-button @click="getData">重新加载</el-button>
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<script>
import { getPayConfig, savePayConfig } from '@/api/biz/payConfig'

export default {
  name: 'BizPayConfig',
  data() {
    return {
      loading: false,
      submitLoading: false,
      channelOptions: [
        { label: '易接口（默认）', value: 'epay' },
        { label: '微信直连（官）', value: 'wxpay' }
      ],
      form: {
        payChannel: 'wxpay',

        wxpayAppid: '',
        wxpaySecret: '',
        wxpayMchid: '',
        wxpayKey: '',

        epayApi: '',
        epayId: '',
        epayKey: ''
      }
    }
  },
  created() {
    this.getData()
  },
  methods: {
    async getData() {
      this.loading = true
      try {
        const res = await getPayConfig()
        const data = res.data || {}

        this.form.payChannel = data.payChannel || 'wxpay'

        this.form.wxpayAppid = data.wxpayAppid || ''
        this.form.wxpaySecret = data.wxpaySecret || ''
        this.form.wxpayMchid = data.wxpayMchid || ''
        this.form.wxpayKey = data.wxpayKey || ''

        this.form.epayApi = data.epayApi || ''
        this.form.epayId = data.epayId || ''
        this.form.epayKey = data.epayKey || ''

      } finally {
        this.loading = false
      }
    },
    validateForm() {
      if (!this.form.payChannel) {
        this.$modal.msgError('请选择支付通道')
        return false
      }

      if (this.form.payChannel === 'wxpay') {
        if (!this.form.wxpayAppid) {
          this.$modal.msgError('请输入微信 AppID')
          return false
        }
        if (!this.form.wxpaySecret) {
          this.$modal.msgError('请输入微信 AppSecret')
          return false
        }
        if (!this.form.wxpayMchid) {
          this.$modal.msgError('请输入微信商户号 MCHID')
          return false
        }
        if (!this.form.wxpayKey) {
          this.$modal.msgError('请输入微信 KeyV2')
          return false
        }
      }

      if (this.form.payChannel === 'epay') {
        if (!this.form.epayApi) {
          this.$modal.msgError('请输入易支付接口地址')
          return false
        }
        if (!this.form.epayId) {
          this.$modal.msgError('请输入易支付商户ID')
          return false
        }
        if (!this.form.epayKey) {
          this.$modal.msgError('请输入易支付密钥')
          return false
        }
      }
      return true
    },
    async handleSubmit() {
      if (!this.validateForm()) {
        return
      }

      this.submitLoading = true
      try {
        await savePayConfig(this.form)
        this.$modal.msgSuccess('保存成功')
        await this.getData()
      } finally {
        this.submitLoading = false
      }
    }
  }
}
</script>

<style scoped>
.section-title {
  margin: 10px 0 20px;
  padding-left: 10px;
  border-left: 4px solid #67c23a;
  color: #67c23a;
  font-size: 16px;
  font-weight: 600;
}

.qrcode-preview {
  width: 120px;
  height: 120px;
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  object-fit: contain;
  background: #fff;
}
</style>
