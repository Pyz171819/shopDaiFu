<template>
  <div class="cashier-default">
    <!-- 用户头像和昵称 -->
    <div class="user-header">
      <img :src="userAvatar" class="avatar" alt="头像">
      <div class="user-info">
        <div class="nick">{{ userNick }}</div>
        <div class="slogan">{{ userSlogan }}</div>
      </div>
    </div>

    <!-- 支付金额卡片 -->
    <div class="card">
      <!-- 已支付状态 -->
      <div v-if="isPaid" style="text-align:center; padding: 10px 0;">
        <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 12px;">
          <svg viewBox="0 0 1024 1024" width="26" height="26" style="margin-right: 10px;">
            <path d="M512 0C229.23 0 0 229.23 0 512s229.23 512 512 512 512-229.23 512-512S794.77 0 512 0z" fill="#FFC300"/>
            <path d="M426.67 746.67a32 32 0 0 1-22.61-9.39l-213.33-213.33a32 32 0 1 1 45.27-45.27L426.67 669.33l362.66-362.66a32 32 0 0 1 45.27 45.27l-385.33 385.33a32 32 0 0 1-22.6 9.4z" fill="#FFFFFF"/>
          </svg>
          <span style="font-size: 20px; font-weight: bold; color: #333;">美团用户已付款</span>
        </div>
        <div class="pay-amount">{{ order.money }}</div>
        <div style="color: #888; font-size: 14px; margin-bottom: 20px;">微信支付</div>
      </div>

      <!-- 未支付状态 -->
      <div v-else style="text-align:center;">
        <div style="font-size: 16px; font-weight: bold; margin-bottom: 15px; color: #333;">需付款</div>
        <div class="pay-amount">{{ order.money }}</div>
        <div v-if="!isExpired" class="timer-wrap">
          支付剩余时间 
          <span class="timer-box">{{ minutes }}</span> : 
          <span class="timer-box">{{ seconds }}</span>
        </div>
      </div>

      <!-- 付款须知 -->
      <div class="notice-box">
        <div class="notice-title">付款须知</div>
        1.代付订单创建成功后15分钟内未付款，订单会自动取消，你可以重新下单。<br>
        2.当代付订单退款成功后，实付金额将原路退还代付人。
      </div>

      <!-- 支付按钮 -->
      <div v-if="isPaid">
        <a href="javascript:;" class="btn-main" @click="$router.push({ name: 'home' })">知道了</a>
      </div>
      <div v-else>
        <button 
          v-if="isExpired" 
          class="btn-main btn-disabled"
        >
          订单已过期
        </button>
        <button 
          v-else 
          class="btn-main"
          @click="handlePay"
        >
          为好友买单
        </button>

        <!-- 分享按钮 - 仅未支付且未过期时显示 -->
        <div v-if="!isExpired" style="margin-top: 25px; padding-top: 15px; border-top: 1px dashed #eee;">
          <button class="btn-main btn-poster" @click="handleGeneratePoster">生成海报分享</button>
          <button class="btn-main btn-card-share" @click="handleCardShare">微信卡片分享</button>
        </div>
      </div>
    </div>

    <!-- 商品列表卡片 -->
    <div class="card">
      <!-- 多商品 -->
      <div v-if="isMultiItem">
        <div 
          v-for="(item, index) in orderItems" 
          :key="index" 
          class="product-section"
        >
          <div class="shop-name-header">
            {{ item.shopName || '外卖订单' }}
          </div>
          <div class="product-row" style="margin-top: 0;">
            <img :src="getImageUrl(item.image)" class="p-img" alt="商品">
            <div class="p-info">
              <div class="p-name">{{ item.name }}</div>
              <div style="display:flex; justify-content:space-between; align-items:center;">
                <span style="color:#999; font-size:12px;">X {{ item.quantity || 1 }}</span>
                <span style="font-weight:bold; font-family:Arial;">¥ {{ item.price }}</span>
              </div>
            </div>
          </div>
        </div>
        <div class="total-summary">
          共 {{ orderItems.length }} 件商品，合计 
          <span style="color:#000;font-weight:bold;">¥{{ order.money }}</span>
        </div>
      </div>

      <!-- 单商品 -->
      <div v-else>
        <div class="shop-name-header">
          {{ shopName }}
        </div>
        <div class="product-row" style="border-bottom:none; margin-top:10px;">
          <img :src="productImage" class="p-img" alt="商品">
          <div class="p-info">
            <div class="p-name">{{ productName }}</div>
            <div style="display:flex; justify-content:space-between; align-items:center;">
              <span style="color:#999; font-size:12px;">X 1</span>
              <span style="font-weight:bold;">¥ {{ order.money }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'CashierDefault',
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
    },
    shopName() {
      if (this.orderItems.length > 0 && this.orderItems[0].shopName) {
        return this.orderItems[0].shopName
      }
      return '外卖订单'
    },
    userNick() {
      return '须尽欢'
    },
    userAvatar() {
      return 'https://img.yzcdn.cn/vant/cat.jpeg'
    },
    userSlogan() {
      return 'Hi~你和我的距离只差一顿外卖~'
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
    },
    handleGeneratePoster() {
      this.$message.info('生成海报功能开发中...')
    },
    handleCardShare() {
      this.$message.info('请点击右上角【...】选择【发送给朋友】')
    }
  }
}
</script>

<style scoped>
* { box-sizing: border-box; }

.cashier-default {
  min-height: 100vh;
  background-color: #F5F5F5;
  padding-bottom: 20px;
}

.user-header { 
  padding: 20px 15px; 
  display: flex; 
  align-items: center; 
}

.avatar { 
  width: 50px; 
  height: 50px; 
  border-radius: 50%; 
  object-fit: cover; 
  margin-right: 12px; 
  border: 1px solid #fff; 
}

.user-info .nick { 
  font-size: 16px; 
  font-weight: bold; 
  margin-bottom: 4px; 
  color: #222; 
}

.user-info .slogan { 
  font-size: 12px; 
  color: #666; 
}

.card { 
  background: #fff; 
  border-radius: 12px; 
  margin: 0 12px 12px; 
  padding: 25px 20px; 
  box-shadow: 0 2px 6px rgba(0,0,0,0.02); 
}

.pay-amount { 
  text-align: center; 
  font-size: 38px; 
  font-weight: bold; 
  margin-bottom: 5px; 
  font-family: Arial, sans-serif; 
}

.pay-amount::before { 
  content: '¥'; 
  font-size: 22px; 
  margin-right: 4px; 
}

.timer-wrap { 
  display: flex; 
  justify-content: center; 
  align-items: center; 
  margin-bottom: 30px; 
  font-size: 14px; 
  color: #333; 
}

.timer-box { 
  background: #333; 
  color: #fff; 
  padding: 2px 4px; 
  border-radius: 4px; 
  margin: 0 4px; 
  font-weight: bold; 
}

.notice-box { 
  background: #FEF8E5; 
  border-radius: 8px; 
  padding: 15px; 
  font-size: 12px; 
  color: #8B572A; 
  line-height: 1.8; 
  margin-bottom: 15px; 
}

.notice-title { 
  color: #8B572A; 
  margin-bottom: 5px; 
  font-weight: bold; 
  font-size: 13px; 
}

.btn-main { 
  display: block; 
  width: 100%; 
  background: #FDD934; 
  color: #000; 
  font-size: 16px; 
  font-weight: bold; 
  text-align: center; 
  padding: 14px 0; 
  border-radius: 25px; 
  text-decoration: none; 
  border: none; 
  cursor: pointer; 
  margin-top: 15px; 
}

.btn-disabled {
  background: #ccc !important;
  cursor: not-allowed !important;
}

.btn-poster { 
  background: #fff; 
  border: 1px solid #FDD934; 
  color: #F5A623; 
  margin-top: 10px; 
} 

.btn-card-share { 
  background: #fff; 
  border: 1px solid #07c160; 
  color: #07c160; 
  margin-top: 10px; 
}

.product-section {
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 2px solid #f0f0f0;
}

.product-section:last-child {
  margin-bottom: 0;
  border-bottom: none;
  padding-bottom: 0;
}

.shop-name-header {
  font-size: 13px;
  color: #999;
  font-weight: 600;
  padding: 8px 12px;
  background: #fafafa;
  border-radius: 6px;
  margin-bottom: 12px;
  border-left: 3px solid #FDD934;
}

.total-summary {
  text-align: right;
  margin-top: 15px;
  padding-top: 15px;
  border-top: 2px dashed #e5e5e5;
  font-size: 14px;
  color: #666;
  font-weight: 500;
}

.product-row { 
  display: flex; 
  align-items: center; 
  margin-top: 15px; 
  padding-bottom: 15px; 
  border-bottom: 1px dashed #f5f5f5; 
}

.product-row:last-child { 
  border-bottom: none; 
  padding-bottom: 0; 
}

.p-img { 
  width: 60px; 
  height: 60px; 
  background: #f8f8f8; 
  border-radius: 6px; 
  margin-right: 12px; 
  object-fit: cover; 
  flex-shrink: 0; 
}

.p-info { 
  flex: 1; 
  display: flex; 
  flex-direction: column; 
  justify-content: center; 
}

.p-name { 
  font-size: 14px; 
  color: #333; 
  line-height: 1.4; 
  margin-bottom: 4px; 
  height: 40px; 
  overflow: hidden; 
  display: -webkit-box; 
  -webkit-line-clamp: 2; 
  -webkit-box-orient: vertical; 
}
</style>
