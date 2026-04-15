<template>
  <div class="cashier-didi">
    <div class="header-section">
      <div class="quote-text">"这款好物来自得物App，我超喜欢它 请你快来帮我付个款，谢啦！"</div>
    </div>

    <div class="unified-card">
      <div class="price-section">
        <img v-if="isPaid" src="/dewu_end.png" class="price-seal">
        <img v-else-if="isExpired" src="/dwyz.png" class="price-seal">
        
        <div class="price-label">付款金额</div>
        <div class="price-amount"><span style="font-size:28px; margin-right:2px;">¥</span>{{ order.money }}</div>
        <div class="timer-wrap">
          <span v-if="isPaid">订单已完成支付</span>
          <span v-else>
            剩余支付时间 
            <span class="timer-box">{{ minutes }}</span> : 
            <span class="timer-box">{{ seconds }}</span>
          </span>
        </div>
      </div>

      <div class="card-divider"></div>

      <div class="product-section">
        <div class="row-titles">
          <span>付款商品</span>
          <span>收货人：<span class="receiver-tag">*试</span></span>
        </div>

        <div v-if="isMultiItem">
          <div 
            v-for="(item, index) in orderItems" 
            :key="index" 
            class="product-item"
            style="margin-bottom: 15px; border-bottom: 1px dashed #f5f5f5; padding-bottom: 10px;"
          >
            <img :src="getImageUrl(item.image)" class="p-img" style="width:60px; height:60px;">
            <div class="p-box" style="flex:1; display:flex; flex-direction:column; justify-content:center;">
              <div class="p-title" style="font-size:14px; -webkit-line-clamp: 1; overflow:hidden; display:-webkit-box; -webkit-box-orient:vertical;">
                {{ item.name }}
              </div>
              <div style="display:flex; justify-content:space-between; margin-top:5px; font-size:13px; color:#666;">
                <span style="font-weight:bold; color:#000;">¥{{ item.price }}</span>
                <span>x{{ item.quantity || 1 }}</span>
              </div>
            </div>
          </div>
          <div style="text-align:right; font-size:13px; color:#666; margin-bottom:15px;">
            共 {{ orderItems.length }} 件，合计 
            <span style="font-weight:bold; color:#000;">¥{{ order.money }}</span>
          </div>
        </div>

        <div v-else class="product-item">
          <img :src="productImage" class="p-img">
          <div class="p-box">
            <div class="p-title">{{ productName }}</div>
          </div>
        </div>
        
        <button 
          v-if="isPaid || isExpired"
          class="btn-pay-cyan btn-disabled"
        >
          {{ isPaid ? '已完成支付' : '订单已过期' }}
        </button>
        <button 
          v-else
          class="btn-pay-cyan"
          @click="handlePay"
        >
          豪爽支付
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'CashierDidi',
  props: {
    order: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      remainingSeconds: 0,
      timer: null
    }
  },
  computed: {
    isPaid() {
      return this.order.status === 1
    },
    isExpired() {
      return this.remainingSeconds <= 0 && !this.isPaid
    },
    minutes() {
      const m = Math.floor(this.remainingSeconds / 60)
      return m < 10 ? '0' + m : m
    },
    seconds() {
      const s = this.remainingSeconds % 60
      return s < 10 ? '0' + s : s
    },
    orderItems() {
      try {
        if (typeof this.order.items === 'string') {
          return JSON.parse(this.order.items)
        }
        return this.order.items || []
      } catch (e) {
        return []
      }
    },
    isMultiItem() {
      return this.orderItems.length > 1
    },
    productName() {
      return this.order.orderName || '商品订单'
    },
    productImage() {
      if (this.orderItems.length > 0) {
        return this.getImageUrl(this.orderItems[0].image)
      }
      return ''
    }
  },
  mounted() {
    this.calculateRemainingTime()
    this.startTimer()
  },
  beforeDestroy() {
    this.stopTimer()
  },
  methods: {
    calculateRemainingTime() {
      // 根据订单的过期时间计算剩余秒数
      if (this.isPaid || !this.order.expireTime) {
        this.remainingSeconds = 0
        return
      }

      const expireTime = new Date(this.order.expireTime).getTime()
      const now = Date.now()
      const diff = Math.floor((expireTime - now) / 1000)
      
      this.remainingSeconds = diff > 0 ? diff : 0
    },
    startTimer() {
      if (this.isPaid || this.remainingSeconds <= 0) return
      
      this.timer = setInterval(() => {
        if (this.remainingSeconds > 0) {
          this.remainingSeconds--
        } else {
          this.stopTimer()
        }
      }, 1000)
    },
    stopTimer() {
      if (this.timer) {
        clearInterval(this.timer)
        this.timer = null
      }
    },
    getImageUrl(image) {
      if (!image) return ''
      if (image.startsWith('http://') || image.startsWith('https://')) {
        return image
      }
      return `/api${image}`
    },
    async handlePay() {
      try {
        const { getClientType } = await import('@/utils/device')
        const { payOrder } = await import('@/api/order')
        
        const payData = {
          tradeNo: this.order.outTradeNo,
          clientType: getClientType(),
          openId: '' // 微信内支付时需要，暂时留空
        }
        
        this.$message.loading('正在发起支付...', 0)
        const res = await payOrder(payData)
        this.$message.destroy()
        
        if (res.code === 200) {
          // 支付发起成功，后续根据返回数据处理
          console.log('支付返回数据：', res.data)
          this.$message.success('支付发起成功')
          // TODO: 根据后端返回的数据进行下一步操作
        } else {
          this.$message.error(res.msg || '支付发起失败')
        }
      } catch (error) {
        this.$message.destroy()
        this.$message.error(error.message || '支付请求失败')
      }
    }
  }
}
</script>

<style scoped>
* { box-sizing: border-box; }

.cashier-didi {
  min-height: 100vh;
  background-color: #F8F8F8;
  padding-bottom: 40px;
}

.header-section {
  background: linear-gradient(135deg, #00C2C9 0%, #00A8B0 100%);
  padding: 40px 20px 30px;
  color: #fff;
}

.quote-text {
  font-size: 15px;
  font-weight: 600;
  line-height: 1.6;
  text-align: center;
}

.unified-card { 
  background: #fff; 
  margin: -20px 12px 0 12px; 
  border-radius: 12px; 
  position: relative; 
  z-index: 10; 
  padding-bottom: 25px; 
  box-shadow: 0 4px 15px rgba(0,0,0,0.08); 
}

.price-section { 
  padding: 40px 20px 25px; 
  text-align: center; 
  position: relative; 
}

.price-seal { 
  position: absolute; 
  top: 10px; 
  right: 15px; 
  width: 75px; 
  height: auto; 
  z-index: 5; 
  pointer-events: none; 
}

.price-label {
  font-size:16px; 
  color:#333; 
  margin-bottom:12px;
}

.price-amount { 
  font-size: 48px; 
  font-weight: bold; 
  color: #000; 
  font-family: "Arial", sans-serif; 
}

.timer-wrap { 
  font-size: 14px; 
  color: #999; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  margin-top: 15px; 
}

.timer-box { 
  border: 1px solid #E5E5E5; 
  padding: 1px 4px; 
  margin: 0 3px; 
  color: #333; 
  font-weight: bold; 
  font-family: monospace; 
}

.card-divider { 
  margin: 10px 0 25px; 
  border-top: 1px dashed #F2F2F2; 
}

.product-section { 
  padding: 0 20px; 
}

.row-titles { 
  display: flex; 
  justify-content: space-between; 
  align-items: center; 
  font-size: 15px; 
  color: #333; 
  margin-bottom: 20px; 
}

.row-titles span:nth-child(2) { 
  text-align: right; 
  flex: 1; 
}

.receiver-tag { 
  font-weight: bold; 
  color: #000; 
}

.product-item { 
  display: flex; 
  align-items: flex-start; 
  margin-bottom: 25px; 
}

.p-img { 
  width: 85px; 
  height: 85px; 
  border-radius: 8px; 
  object-fit: cover; 
  margin-right: 15px; 
  background: #fafafa; 
}

.p-title { 
  font-size: 15px; 
  color: #333; 
  font-weight: bold; 
  line-height: 1.5; 
}

.btn-pay-cyan { 
  display: block; 
  width: 100%; 
  background-color: #00C2C9; 
  color: #fff; 
  font-size: 18px; 
  font-weight: bold; 
  text-align: center; 
  padding: 15px 0; 
  border-radius: 8px; 
  text-decoration: none; 
  margin-bottom: 20px; 
  border: none;
  cursor: pointer;
}

.btn-disabled { 
  background-color: #CCCCCC !important; 
  box-shadow: none; 
  cursor: default; 
}
</style>
