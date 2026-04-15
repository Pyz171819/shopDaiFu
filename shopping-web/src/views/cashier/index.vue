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
import CashierDefault from './templates/CashierDefault.vue'
import CashierXianyu from './templates/CashierXianyu.vue'
import CashierDidi from './templates/CashierDidi.vue'

export default {
  name: 'CashierPage',
  components: {
    CashierDefault,
    CashierXianyu,
    CashierDidi
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
      // 从 URL 参数获取订单号
      const outTradeNo = this.$route.query.outTradeNo
      
      if (!outTradeNo) {
        this.$message.error('订单号不存在')
        this.$router.push({ name: 'home' })
        return
      }

      this.loading = true
      
      try {
        // 调用后端接口查询订单详情
        const res = await getOrderDetail(outTradeNo)
        
        if (res.code !== 200 || !res.data) {
          throw new Error(res.msg || '订单查询失败')
        }

        const order = res.data
        this.orderData = order
        
        // 根据 tpl 字段选择模板
        const tplMap = {
          'cashier': 'CashierDefault',    // 美团/默认
          'cashier1': 'CashierXianyu',    // 闲鱼（1:1）
          'cashier8': 'CashierDidi'       // 滴滴代付（新）
        }
        
        this.currentTemplate = tplMap[order.tpl] || 'CashierDefault'
        
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
