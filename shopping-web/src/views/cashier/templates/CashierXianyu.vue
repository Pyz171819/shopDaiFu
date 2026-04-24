<template>
  <div class="cashier-xianyu">
    <div class="header">
      <img :src="sellerAvatar" class="u-avatar" alt="seller">
      <div class="u-info">
        <div class="u-left-group">
          <div class="u-name">{{ sellerName }}</div>
          <div class="u-tag">鱼小铺 L1</div>
        </div>
        <div class="u-status">1小时前来过 | 济南</div>
      </div>
    </div>

    <div class="price-section">
      <span class="p-symbol">￥</span>
      <span class="p-val">{{ order.money }}</span>
      <span class="p-badge-red">2人小刀价</span>
    </div>

    <div class="p-sub-row">
      <span class="p-sub-tag">包邮</span>
    </div>

    <div class="p-stats-row">
      直接买 <span class="text-del">￥{{ originalPrice }}</span> | 2人想要 | 128人浏览
    </div>

    <div class="content">
      <div class="safety-tip">
        <div class="st-title">闲鱼交易须知 <span class="info-mark">i</span></div>
        <div class="st-desc">买前了解退货规则，保障你的交易权益</div>
      </div>

      <div class="c-text">{{ productName }}</div>
    </div>

    <div class="prod-img-box">
      <img :src="productImage" class="prod-img" alt="product">
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
      <div class="header">
        <img :src="sellerAvatar" class="u-avatar" alt="seller" crossorigin="anonymous">
        <div class="u-info">
          <div class="u-left-group">
            <div class="u-name">{{ sellerName }}</div>
            <div class="u-tag">鱼小铺 L1</div>
          </div>
          <div class="poster-logo-right">闲鱼</div>
        </div>
      </div>
      <div class="prod-img-box">
        <img :src="productImage" class="prod-img" alt="product" crossorigin="anonymous">
      </div>
      <div class="price-section">
        <span class="p-symbol">￥</span>
        <span class="p-val">{{ order.money }}</span>
      </div>
      <div class="content">
        <div class="c-text">{{ productName }}</div>
      </div>
      <div class="poster-qr-wrap">
        <div ref="posterQrContainer" class="poster-qr"></div>
        <div class="poster-qr-tip">长按识别二维码</div>
      </div>
    </div>
  </div>
</template>

<script>
import shareTemplateMixin from './shareTemplateMixin'

export default {
  name: 'CashierXianyu',
  mixins: [shareTemplateMixin],
  computed: {
    pageTitle() {
      return this.getDefaultShareTitle()
    },
    sellerName() {
      return this.order.shopName || this.order.shop_name || '卖家'
    },
    sellerAvatar() {
      if (this.userInfo?.avatar) {
        return this.getImageUrl(this.userInfo.avatar)
      }
      return 'https://img.yzcdn.cn/vant/cat.jpeg'
    },
    originalPrice() {
      const money = Number(this.order.money || 0)
      return (money * 1.5).toFixed(2)
    }
  },
  methods: {
    getDefaultShareTitle() {
      return '帮我看看闲鱼宝贝'
    },
    getDefaultShareDesc() {
      return '成色很好，物超所值，快帮我付款吧，这个价格真的很划算。'
    },
    getPosterQrOptions() {
      return {
        width: 100,
        height: 100,
        colorLight: '#ffffff'
      }
    }
  }
}
</script>

<style scoped>
* { box-sizing: border-box; }

.cashier-xianyu {
  min-height: 100vh;
  padding-bottom: 24px;
  background: #fff;
  color: #111;
}

.header {
  display: flex;
  align-items: center;
  padding: 12px 15px;
  background: #fff;
}

.u-avatar {
  width: 36px;
  height: 36px;
  margin-right: 10px;
  border: 1px solid #f0f0f0;
  border-radius: 50%;
  object-fit: cover;
}

.u-info {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.u-left-group {
  display: flex;
  align-items: center;
}

.u-name {
  margin-right: 6px;
  color: #111;
  font-size: 15px;
  font-weight: bold;
}

.u-tag {
  display: flex;
  align-items: center;
  padding: 1px 4px;
  border-radius: 2px;
  background: #E6F1FC;
  color: #108EE9;
  font-size: 10px;
  font-weight: bold;
  white-space: nowrap;
}

.u-status {
  color: #999;
  font-size: 11px;
  text-align: right;
  white-space: nowrap;
}

.price-section {
  display: flex;
  align-items: flex-end;
  padding: 5px 15px;
}

.p-symbol {
  margin-bottom: 3px;
  color: #FF2B2B;
  font-size: 18px;
  font-weight: bold;
}

.p-val {
  color: #FF2B2B;
  font-family: Arial, sans-serif;
  font-size: 32px;
  font-weight: bold;
  line-height: 1;
}

.p-badge-red {
  margin-left: 8px;
  margin-bottom: 4px;
  padding: 2px 5px;
  border-radius: 4px;
  background: rgba(255, 43, 43, 0.08);
  color: #FF2B2B;
  font-size: 11px;
}

.p-sub-row {
  margin-top: 5px;
  padding: 0 15px;
}

.p-sub-tag {
  display: inline-block;
  padding: 0 2px;
  border: 1px solid #FF2B2B;
  border-radius: 2px;
  color: #FF2B2B;
  font-size: 10px;
}

.p-stats-row {
  display: flex;
  align-items: center;
  margin-top: 8px;
  padding: 0 15px;
  color: #999;
  font-size: 12px;
}

.text-del {
  margin: 0 2px;
  text-decoration: line-through;
}

.content {
  padding: 10px 15px 0;
}

.safety-tip {
  margin-bottom: 15px;
  color: #333;
  font-size: 12px;
  line-height: 1.6;
}

.st-title {
  display: flex;
  align-items: center;
  margin-bottom: 4px;
  font-weight: bold;
}

.info-mark {
  display: inline-flex;
  width: 14px;
  height: 14px;
  align-items: center;
  justify-content: center;
  margin-left: 4px;
  border-radius: 50%;
  background: #eee;
  color: #999;
  font-size: 10px;
}

.st-desc {
  display: block;
  color: #999;
}

.c-text {
  display: block;
  margin: 0 0 20px;
  color: #000;
  font-size: 16px;
  font-weight: 500;
  line-height: 1.6;
  text-align: left;
  white-space: pre-wrap;
}

.prod-img-box {
  margin-bottom: 20px;
  padding: 0 15px;
}

.prod-img {
  display: block;
  width: 100%;
  border-radius: 12px;
}

.floating-bar {
  position: fixed;
  right: 15px;
  bottom: 120px;
  z-index: 900;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.float-btn {
  display: flex;
  width: 48px;
  height: 48px;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: 50%;
  background: #fff;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
  color: #666;
  font-size: 10px;
}

.float-icon {
  color: #FFDA44;
  font-size: 20px;
  line-height: 1;
}

.poster-modal {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
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
  border-radius: 12px;
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
  border: 3px solid #fff0b6;
  border-top-color: #FFDA44;
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
  background: #fff;
}

.poster-logo-right {
  margin-left: auto;
  color: #FFDA44;
  font-size: 20px;
  font-weight: 900;
}

.poster-qr-wrap {
  padding: 20px;
  text-align: center;
}

.poster-qr {
  display: inline-block;
}

.poster-qr-tip {
  margin-top: 10px;
  color: #999;
  font-size: 12px;
}

@keyframes sharePreparingSpin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
