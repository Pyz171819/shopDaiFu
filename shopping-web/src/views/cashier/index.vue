<template>
  <div class="cashier-page">
    <component
      :is="currentTemplate"
      v-if="currentTemplate && !loading"
      :order="orderData"
    />
    <div v-else class="loading-state">
      <div class="loading-spinner"></div>
      <p>{{ loading ? '加载订单信息中...' : '加载中...' }}</p>
    </div>
  </div>
</template>

<script>
import { getOrderDetail } from '@/api/order'
import CashierMeituan from './templates/CashierMeituan.vue'
import CashierCtrip from './templates/CashierCtrip.vue'
import CashierPinduoduo from './templates/CashierPinduoduo.vue'

export default {
  name: 'CashierPage',
  components: {
    CashierMeituan,
    CashierCtrip,
    CashierPinduoduo
  },
  data() {
    return {
      orderData: null,
      currentTemplate: null,
      loading: true
    }
  },
  created() {
    this.loadOrder()
  },
  methods: {
    async loadOrder() {
      const outTradeNo = this.$route.query.outTradeNo

      if (!outTradeNo) {
        this.$message.error('订单号不存在')
        this.$router.push({ name: 'home' })
        return
      }

      this.loading = true

      try {
        const res = await getOrderDetail(outTradeNo)

        if (res.code !== 200 || !res.data) {
          throw new Error(res.msg || '订单查询失败')
        }

        const order = res.data
        this.orderData = order

        const tplMap = {
          cashier: 'CashierMeituan',
          cashier2: 'CashierCtrip',
          cashier4: 'CashierPinduoduo'
        }

        this.currentTemplate = tplMap[order.tpl] || 'CashierMeituan'
      } catch (error) {
        this.$message.error(error.message || '订单查询失败')
        this.$router.push({ name: 'home' })
      } finally {
        this.loading = false
      }
    }
  }
}
</script>

<style scoped>
.cashier-page {
  min-height: 100vh;
  background: #F5F5F5;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  color: #666;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #f3f3f3;
  border-top: 3px solid #FDD934;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 15px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>
