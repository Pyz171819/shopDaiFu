<template>
  <div class="cashier-douyin">
    <div class="page-header-text">
      <div class="ph-title">朋友代付</div>
      <div v-if="!isExpired && !isPaid" class="ph-timer">
        剩 <span>{{ timerText }}</span> 订单关闭
      </div>
      <div v-else class="ph-timer">
        {{ isPaid ? '订单已支付' : '支付申请已失效' }}
      </div>
    </div>

    <div class="main-card">
      <div class="user-row">
        <img :src="userAvatar" class="u-avatar" alt="avatar">
        <div class="u-info">
          <div class="u-name">
            {{ userNick }}
            <span v-if="!isExpired && !isPaid" class="u-verify" @click="showVerifyToast">校验姓名</span>
          </div>
          <div class="u-msg">{{ shareDescText }}</div>
        </div>
      </div>

      <div class="inner-gray-box" :class="{ 'expired-dim': isExpired }">
        <div class="price-row">
          <span class="p-symbol">￥</span>
          <span class="p-val">{{ order.money }}</span>
          <span class="p-tag">{{ statusText }}</span>
        </div>

        <div v-if="isMultiItem">
          <div v-for="(item, index) in orderItems" :key="index" class="prod-row">
            <img :src="getImageUrl(item.image)" class="prod-img" alt="product">
            <div class="prod-info-col">
              <div class="prod-name">{{ item.name }}</div>
              <div class="prod-count">￥{{ item.price }} x {{ item.quantity || 1 }}</div>
            </div>
          </div>
        </div>
        <div v-else class="prod-row">
          <img :src="productImage" class="prod-img" alt="product">
          <div class="prod-info-col">
            <div class="prod-name">{{ productName }}</div>
            <div class="prod-count">x1</div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="!isExpired && !isPaid" class="footer-tips">
      如果订单申请退款，已支付金额将原路退还给你。
    </div>

    <div v-if="!isPaid && !isExpired" class="floating-bar">
      <button class="float-btn" @click="handleCardShare" :disabled="isBindingWechatShare">
        <span class="float-icon">⇪</span>
        卡片
      </button>
      <button class="float-btn" @click="handleGeneratePoster" :disabled="isGeneratingPoster">
        <span class="float-icon">▧</span>
        海报
      </button>
    </div>

    <button class="douyin-official-btn" type="button" @click="goDouyinOfficial">消息告诉Ta</button>

    <div class="dy-footer-logo">抖音支付 | 9亿人都在用</div>

    <div v-if="showVerify" class="verify-toast">校验成功</div>

    <div v-if="showPosterModal" class="poster-modal" @click="closePosterModal">
      <div class="poster-modal-close">×</div>
      <img v-if="posterImageUrl" :src="posterImageUrl" class="poster-image" alt="poster">
      <div class="poster-tip">长按保存图片分享</div>
    </div>

    <div v-if="showSharePreparingModal" class="share-preparing-modal">
      <div class="share-preparing-card">
        <div class="share-preparing-spinner"></div>
        <div class="share-preparing-title">正在准备微信分享功能</div>
        <div class="share-preparing-text">请稍候，准备完成后再点击右上角三个点进行分享</div>
      </div>
    </div>

    <div ref="posterCanvas" class="poster-canvas">
      <div class="page-header-text poster-header">
        <div class="ph-title">朋友代付</div>
      </div>
      <div class="main-card poster-main-card">
        <div class="user-row">
          <img :src="userAvatar" class="u-avatar" alt="avatar" crossorigin="anonymous">
          <div class="u-info">
            <div class="u-name">{{ userNick }}</div>
            <div class="u-msg">{{ shareDescText }}</div>
          </div>
        </div>
        <div class="inner-gray-box">
          <div class="price-row">
            <span class="p-symbol">￥</span>
            <span class="p-val">{{ order.money }}</span>
          </div>
          <div v-if="isMultiItem">
            <div v-for="(item, index) in orderItems.slice(0, 3)" :key="index" class="prod-row">
              <img :src="getImageUrl(item.image)" class="prod-img" alt="product" crossorigin="anonymous">
              <div class="prod-info-col">
                <div class="prod-name">{{ item.name }}</div>
                <div class="prod-count">￥{{ item.price }} x {{ item.quantity || 1 }}</div>
              </div>
            </div>
            <div v-if="orderItems.length > 3" class="poster-more">... 等 {{ orderItems.length }} 件商品</div>
          </div>
          <div v-else class="prod-row">
            <img :src="productImage" class="prod-img" alt="product" crossorigin="anonymous">
            <div class="prod-info-col">
              <div class="prod-name">{{ productName }}</div>
              <div class="prod-count">x1</div>
            </div>
          </div>
        </div>
        <div class="poster-qr-wrap">
          <div ref="posterQrContainer" class="poster-qr"></div>
          <div class="poster-qr-tip">长按识别二维码代付</div>
        </div>
      </div>
      <div class="dy-footer-logo poster-footer">抖音支付 | 9亿人都在用</div>
    </div>
  </div>
</template>

<script>
import shareTemplateMixin from './shareTemplateMixin'

export default {
  name: 'CashierDouyin',
  mixins: [shareTemplateMixin],
  data() {
    return {
      showVerify: false,
      verifyTimer: null
    }
  },
  computed: {
    pageTitle() {
      return '抖音代付'
    },
    shareDescText() {
      return this.shareCardConfig?.shareDesc || this.getDefaultShareDesc()
    },
    userNick() {
      return this.userInfo?.nickname || '抖音用户'
    },
    userAvatar() {
      if (this.userInfo?.avatar) {
        return this.getImageUrl(this.userInfo.avatar)
      }
      return 'https://img.yzcdn.cn/vant/cat.jpeg'
    },
    statusText() {
      if (this.isPaid) {
        return '已支付'
      }
      if (this.isExpired) {
        return '支付申请已失效'
      }
      return '待支付'
    },
    timerText() {
      return `00:${this.minutes}:${this.seconds}`
    }
  },
  beforeDestroy() {
    if (this.verifyTimer) {
      clearTimeout(this.verifyTimer)
    }
  },
  mounted() {
    this.fetchShareCardConfig()
  },
  methods: {
    getDefaultShareTitle() {
      return '抖音好物分享'
    },
    getDefaultShareDesc() {
      return '我在抖音看中了这一件宝贝，帮我付一下吧，下次请你吃饭。'
    },
    showVerifyToast() {
      this.showVerify = true
      if (this.verifyTimer) {
        clearTimeout(this.verifyTimer)
      }
      this.verifyTimer = setTimeout(() => {
        this.showVerify = false
      }, 2000)
    },
    getPosterCanvasOptions() {
      return {
        useCORS: true,
        scale: 2,
        allowTaint: false,
        backgroundColor: '#F4F5F6'
      }
    },
    getPosterQrOptions() {
      return {
        width: 100,
        height: 100,
        colorLight: '#ffffff'
      }
    },
    goDouyinOfficial() {
      window.location.href = 'https://www.douyin.com/'
    }
  }
}
</script>

<style scoped>
* { box-sizing: border-box; }

.cashier-douyin {
  min-height: 100vh;
  padding-bottom: 44px;
  background: #F4F5F6;
  color: #161823;
}

.page-header-text {
  text-align: center;
  padding: 40px 0 15px;
}

.ph-title {
  margin-bottom: 8px;
  font-size: 19px;
  font-weight: bold;
}

.ph-timer {
  font-size: 13px;
  color: #888;
}

.ph-timer span {
  color: #FE2C55;
  font-weight: 500;
}

.main-card {
  margin: 10px 16px;
  padding: 20px;
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
}

.user-row {
  display: flex;
  align-items: flex-start;
  margin-bottom: 15px;
}

.u-avatar {
  width: 40px;
  height: 40px;
  margin-right: 12px;
  border-radius: 50%;
  object-fit: cover;
}

.u-info {
  flex: 1;
}

.u-name {
  display: flex;
  align-items: center;
  font-size: 15px;
  font-weight: bold;
}

.u-verify {
  margin-left: 6px;
  padding: 2px 6px;
  border-radius: 4px;
  background: rgba(42, 106, 226, 0.08);
  color: #2a6ae2;
  font-size: 11px;
  font-weight: normal;
}

.u-msg {
  margin-top: 4px;
  color: #999;
  font-size: 13px;
  line-height: 1.4;
}

.inner-gray-box {
  padding: 20px 16px;
  border-radius: 8px;
  background: #F9F9FA;
}

.expired-dim {
  opacity: 0.6;
  filter: grayscale(100%);
}

.price-row {
  display: flex;
  align-items: center;
  margin-bottom: 20px;
}

.p-symbol {
  margin-right: 2px;
  margin-top: 6px;
  font-size: 20px;
  font-weight: bold;
}

.p-val {
  font-family: Arial, sans-serif;
  font-size: 42px;
  font-weight: bold;
}

.p-tag {
  height: 18px;
  margin-left: 10px;
  margin-top: 8px;
  padding: 1px 4px;
  border: 1px solid rgba(254, 44, 85, 0.3);
  border-radius: 3px;
  color: #FE2C55;
  font-size: 11px;
  line-height: 16px;
  white-space: nowrap;
}

.prod-row {
  display: flex;
  align-items: center;
  margin-bottom: 12px;
}

.prod-row:last-child {
  margin-bottom: 0;
}

.prod-img {
  width: 36px;
  height: 36px;
  margin-right: 10px;
  border-radius: 4px;
  background: #eee;
  object-fit: cover;
  flex-shrink: 0;
}

.prod-info-col {
  flex: 1;
  overflow: hidden;
}

.prod-name {
  margin-bottom: 2px;
  overflow: hidden;
  color: #333;
  font-size: 13px;
  line-height: 1.3;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.prod-count {
  color: #999;
  font-size: 12px;
}

.footer-tips {
  margin: 15px 20px;
  color: #999;
  font-size: 12px;
  line-height: 1.5;
}

.floating-bar {
  position: fixed;
  right: 15px;
  bottom: 150px;
  z-index: 900;
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.float-btn {
  width: 48px;
  height: 48px;
  border: none;
  border-radius: 50%;
  background: #fff;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
  color: #666;
  font-size: 10px;
}

.float-icon {
  display: block;
  color: #FE2C55;
  font-size: 18px;
  line-height: 1;
}

.dy-footer-logo {
  position: fixed;
  bottom: 15px;
  left: 0;
  z-index: 101;
  width: 100%;
  text-align: center;
  color: #ccc;
  font-size: 11px;
}

.douyin-official-btn {
  position: fixed;
  left: 50%;
  bottom: 46px;
  z-index: 102;
  width: min(50%, 360px);
  height: 40px;
  border: none;
  border-radius: 22px;
  background: #fff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
  transform: translateX(-50%);
  color: #222;
  font-size: 13px;
  font-weight: 600;
}

.verify-toast {
  position: fixed;
  bottom: 120px;
  left: 50%;
  z-index: 9999;
  padding: 10px 20px;
  border-radius: 50px;
  background: #fff;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
  transform: translateX(-50%);
  color: #333;
  font-size: 14px;
  font-weight: 500;
  white-space: nowrap;
}

.poster-modal {
  position: fixed;
  top: 0;
  left: 0;
  z-index: 9999;
  display: flex;
  width: 100%;
  height: 100%;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.85);
}

.poster-modal-close {
  position: absolute;
  top: 20px;
  right: 20px;
  color: #fff;
  font-size: 35px;
}

.poster-image {
  width: 80%;
  max-width: 330px;
  border-radius: 8px;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.5);
}

.poster-tip {
  margin-top: 15px;
  color: #fff;
  font-size: 14px;
}

.share-preparing-modal {
  position: fixed;
  inset: 0;
  z-index: 10001;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  background: rgba(0, 0, 0, 0.45);
}

.share-preparing-card {
  width: min(100%, 280px);
  padding: 28px 24px;
  border-radius: 16px;
  background: #fff;
  text-align: center;
}

.share-preparing-spinner {
  width: 40px;
  height: 40px;
  margin: 0 auto 16px;
  border: 3px solid #ffd3dc;
  border-top-color: #FE2C55;
  border-radius: 50%;
  animation: sharePreparingSpin 0.9s linear infinite;
}

.share-preparing-title {
  margin-bottom: 8px;
  font-size: 16px;
  font-weight: 600;
}

.share-preparing-text {
  color: #8a8f99;
  font-size: 13px;
  line-height: 1.6;
}

.poster-canvas {
  position: fixed;
  top: 0;
  left: -9999px;
  width: 375px;
  padding-bottom: 30px;
  background: #F4F5F6;
}

.poster-header {
  padding-top: 30px;
}

.poster-main-card {
  border: 1px solid #eee;
  box-shadow: none;
}

.poster-qr-wrap {
  margin-top: 20px;
  padding-top: 15px;
  border-top: 1px dashed #eee;
  text-align: center;
}

.poster-qr {
  display: inline-block;
}

.poster-qr-tip,
.poster-more {
  margin-top: 8px;
  color: #999;
  font-size: 11px;
  text-align: center;
}

.poster-footer {
  position: static;
  margin-top: 20px;
}

@keyframes sharePreparingSpin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
