<template>
  <div class="app-container">
    <el-card shadow="never">
      <div slot="header">
        <span>系统配置</span>
      </div>

      <el-form
        ref="form"
        :model="form"
        label-width="140px"
        v-loading="loading"
      >
        <el-form-item label="站点公告">
          <el-input
            v-model="form.siteNotice"
            type="textarea"
            :rows="4"
            placeholder="请输入站点公告"
            maxlength="500"
            show-word-limit
          />
        </el-form-item>

        <el-form-item label="显示公告">
          <el-switch
            v-model="form.showNotice"
            active-value="1"
            inactive-value="0"
          />
        </el-form-item>

        <el-form-item label="显示轮播图">
          <el-switch
            v-model="form.showSlides"
            active-value="1"
            inactive-value="0"
          />
        </el-form-item>

        <el-form-item label="默认佣金比例">
          <el-input-number
            v-model="form.commissionRate"
            :min="0"
            :max="100"
            :precision="2"
            :step="1"
            controls-position="right"
          />
          <span class="form-tip">单位：%</span>
        </el-form-item>

        <el-form-item label="代付开关">
          <el-switch
            v-model="form.daifuSwitch"
            active-value="1"
            inactive-value="0"
          />
        </el-form-item>

        <el-form-item>
          <el-button
            type="primary"
            :loading="submitLoading"
            @click="handleSubmit"
          >
            保存配置
          </el-button>
          <el-button @click="getConfigData">重新加载</el-button>
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<script>
import { getCommonConfig, saveConfigBatch } from '@/api/biz/config'

export default {
  name: 'BizConfig',
  data() {
    return {
      loading: false,
      submitLoading: false,
      form: {
        siteNotice: '',
        showNotice: '0',
        showSlides: '0',
        commissionRate: 10,
        daifuSwitch: '1'
      }
    }
  },
  created() {
    this.getConfigData()
  },
  methods: {
    async getConfigData() {
      this.loading = true
      try {
        const res = await getCommonConfig()
        const data = res.data || {}
        this.form.siteNotice = data.site_notice || ''
        this.form.showNotice = data.show_notice || '0'
        this.form.showSlides = data.show_slides || '0'
        this.form.commissionRate =
          data.commission_rate !== undefined && data.commission_rate !== ''
            ? Number(data.commission_rate)
            : 10
        this.form.daifuSwitch = data.daifu_switch || '1'
      } finally {
        this.loading = false
      }
    },
    async handleSubmit() {
      if (this.form.commissionRate < 0 || this.form.commissionRate > 100) {
        this.$modal.msgError('默认佣金比例必须在 0-100 之间')
        return
      }

      const payload = {
        configs: {
          site_notice: this.form.siteNotice || '',
          show_notice: this.form.showNotice,
          show_slides: this.form.showSlides,
          commission_rate: String(this.form.commissionRate),
          daifu_switch: this.form.daifuSwitch
        }
      }

      this.submitLoading = true
      try {
        await saveConfigBatch(payload)
        this.$modal.msgSuccess('保存成功')
        await this.getConfigData()
      } finally {
        this.submitLoading = false
      }
    }
  }
}
</script>

<style scoped>
.form-tip {
  margin-left: 10px;
  color: #909399;
  font-size: 12px;
}
</style>
