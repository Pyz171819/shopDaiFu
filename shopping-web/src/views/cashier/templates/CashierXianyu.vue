<template>
  <div class="cashier-xianyu">
    <div class="header-bg">
      <img src="/dingbu.png" class="bg-img" alt="bg">
      <div class="user-interaction">
        <img :src="userAvatar" class="u-avatar">
        <div class="bubble">
          我在京东上挑好了商品，是时候该你仗义疏财啦，快帮我付个款吧~
        </div>
      </div>
    </div>

    <div class="jd-card">
      <div class="amount-row">
        <span class="amount-label" :style="isPaid ? 'color:#bbb;' : ''">代付金额</span>
        <span class="receiver-info">收货人：*{{ displayNick }}</span>
      </div>

      <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 10px;">
        <div class="price-large" :style="isPaid ? 'color:#bbb; font-size:30px; font-weight:normal;' : ''">
          ¥{{ order.money }}
        </div>
        
        <div class="timer-row">
          <span class="timer-label" :style="isPaid ? 'color:#bbb;' : ''">剩余支付时间</span>
          <div class="timer-box" :style="isPaid ? 'color:#bbb;' : ''">
            <span class="t-num">{{ hours }}</span> : 
            <span class="t-num">{{ minutes }}</span> : 
            <span class="t-num">{{ seconds }}</span>
          </div>
        </div>
      </div>

      <img v-if="isPaid" src="/yzf.png" class="stamp-paid-img" alt="已支付">
      <img v-else-if="isExpired" src="/guiqi.png" class="stamp-paid-img stamp-expired" alt="已过期">
    </div>

    <div v-if="!isPaid" class="jd-card">
      <div class="pay-method-title">支付方式</div>
      <div class="pay-row">
        <div class="pay-left">
          <i class="bi bi-wechat" style="color:#09BB07; font-size:22px; margin-right:8px;"></i>
          <span class="pay-name">微信支付</span>
        </div>
        <i class="bi bi-check-circle-fill check-icon"></i>
      </div>
      
      <button 
        v-if="!isExpired" 
        class="btn-pay-now"
        @click="handlePay"
      >
        立即支付
      </button>
      <button v-else class="btn-pay-now" style="background:#ccc; box-shadow:none;">
        订单已过期
      </button>
    </div>

    <div class="jd-card">
      <div class="order-title">代付订单信息</div>
      
      <div v-if="isMultiItem">
        <div 
          v-for="(item, index) in orderItems" 
          :key="index" 
          class="prod-row"
          style="margin-bottom: 12px; border-bottom: 1px dashed #f5f5f5; padding-bottom: 10px;"
        >
          <img :src="getImageUrl(item.image)" class="prod-img" style="width:60px; height:60px;">
          <div class="prod-info">
            <div class="prod-name" style="-webkit-line-clamp: 1;">{{ item.name }}</div>
            <div class="prod-price-row">
              <span class="prod-price">¥{{ item.price }}</span>
              <span class="prod-qty">x{{ item.quantity || 1 }}</span>
            </div>
          </div>
        </div>
        <div style="text-align:right; font-size:12px; color:#999; padding-top:5px;">
          共 {{ orderItems.length }} 件商品，合计 
          <span style="color:#F2270C;font-weight:bold;">¥{{ order.money }}</span>
        </div>
      </div>

      <div v-else class="prod-row">
        <img :src="productImage" class="prod-img">
        <div class="prod-info">
          <div class="prod-name">{{ productName }}</div>
          <div class="prod-price-row">
            <span class="prod-price">¥{{ order.money }}</span>
            <span class="prod-qty">数量：1</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'CashierXianyu',
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
    hours() {
      const h = Math.floor(this.remainingSeconds / 3600)
      return h < 10 ? '0' + h : h
    },
    minutes() {
      const m = Math.floor((this.remainingSeconds % 3600) / 60)
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
    },
    displayNick() {
      return '试'
    },
    userAvatar() {
      return 'https://img.yzcdn.cn/vant/cat.jpeg'
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

.cashier-xianyu {
  min-height: 100vh;
  background-color: #F6F6F6;
  padding-bottom: 80px;
}

.header-bg { 
  position: relative; 
  width: 100%; 
  margin-bottom: -20px; 
  z-index: 0; 
}

.bg-img { 
  width: 100%; 
  display: block; 
  height: auto; 
  position: absolute; 
  top: 0; 
  left: 0; 
  z-index: 1; 
}

.user-interaction { 
  position: relative; 
  z-index: 100; 
  display: flex; 
  align-items: flex-start; 
  padding: 20px 15px 40px 15px; 
}

.u-avatar { 
  width: 50px; 
  height: 50px; 
  border-radius: 50%; 
  object-fit: cover; 
  border: 2px solid #fff; 
  margin-right: 12px; 
  margin-top: 10px; 
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); 
}

.bubble { 
  flex: 1; 
  background: transparent; 
  border: none; 
  box-shadow: none; 
  padding: 20px 15px 20px 20px; 
  position: relative; 
  font-size: 14px; 
  color: #333; 
  line-height: 1.5; 
}

.jd-card { 
  background: #fff; 
  border-radius: 12px; 
  margin: 0 12px 12px; 
  padding: 20px; 
  box-shadow: 0 2px 8px rgba(0,0,0,0.03); 
  position: relative; 
  overflow: hidden; 
  z-index: 10; 
}

.amount-row { 
  display: flex; 
  justify-content: space-between; 
  align-items: center; 
  margin-bottom: 15px; 
}

.amount-label { 
  font-size: 16px; 
  color: #333; 
  font-weight: 500; 
}

.receiver-info { 
  font-size: 14px; 
  color: #666; 
}

.price-large { 
  font-size: 36px; 
  color: #F2270C; 
  font-weight: bold; 
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; 
  letter-spacing: -1px; 
}

.timer-row { 
  display: flex; 
  justify-content: flex-end; 
  align-items: center; 
  gap: 8px; 
}

.timer-label { 
  font-size: 13px; 
  color: #999; 
}

.timer-box { 
  display: flex; 
  align-items: center; 
  font-size: 13px; 
  color: #333; 
  font-variant-numeric: tabular-nums; 
}

.t-num { 
  border: 1px solid #ddd; 
  border-radius: 4px; 
  padding: 1px 5px; 
  margin: 0 3px; 
  font-family: monospace; 
  min-width: 24px; 
  text-align: center; 
  background: #fff; 
}

.pay-method-title { 
  font-size: 14px; 
  color: #666; 
  margin-bottom: 15px; 
}

.pay-row { 
  display: flex; 
  align-items: center; 
  justify-content: space-between; 
  padding: 5px 0; 
}

.pay-left { 
  display: flex; 
  align-items: center; 
}

.check-icon { 
  color: #F2270C; 
  font-size: 20px; 
}

.btn-pay-now { 
  display: block; 
  width: 100%; 
  background: linear-gradient(90deg, #ff3100 0%, #f2270c 100%); 
  color: #fff; 
  font-size: 17px; 
  font-weight: bold; 
  text-align: center; 
  padding: 13px 0; 
  border-radius: 25px; 
  text-decoration: none; 
  margin-top: 15px; 
  box-shadow: 0 4px 12px rgba(242, 39, 12, 0.3); 
  border: none; 
  cursor: pointer;
}

.order-title { 
  font-size: 14px; 
  color: #333; 
  margin-bottom: 15px; 
  font-weight: bold; 
}

.prod-row { 
  display: flex; 
  align-items: center; 
}

.prod-img { 
  width: 80px; 
  height: 80px; 
  border-radius: 6px; 
  object-fit: cover; 
  margin-right: 15px; 
  border: 1px solid #f0f0f0; 
  flex-shrink: 0; 
}

.prod-info { 
  flex: 1; 
  display: flex; 
  flex-direction: column; 
  justify-content: space-between; 
  padding: 2px 0; 
  min-width: 0; 
}

.prod-name { 
  font-size: 14px; 
  color: #333; 
  line-height: 1.4; 
  overflow: hidden; 
  text-overflow: ellipsis; 
  display: -webkit-box; 
  -webkit-line-clamp: 2; 
  -webkit-box-orient: vertical; 
}

.prod-price-row { 
  display: flex; 
  justify-content: space-between; 
  align-items: center; 
  font-size: 14px; 
  margin-top: 5px; 
}

.prod-price { 
  font-family: Arial; 
  font-weight: bold; 
  color: #F2270C; 
}

.prod-qty { 
  color: #999; 
  font-size: 12px; 
}

.stamp-paid-img {
  position: absolute;
  right: 15px;
  top: 50%;
  transform: translateY(-60%) rotate(-15deg);
  width: 100px; 
  height: auto;
  z-index: 20;
  opacity: 0.9;
  pointer-events: none;
}

.stamp-expired {
  filter: grayscale(100%); 
  opacity: 0.6;
}
</style>
