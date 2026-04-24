<template>
  <div class="cashier-taobao">
    <div class="nav-bar"></div>

    <div class="price-section">
      <span class="price-symbol">￥</span>
      <span class="price-val">{{ order.money }}</span>
    </div>

    <div class="main-card">
      <div class="card-header">
        <span>帮付订单信息</span>
        <span class="help-tips">帮我付说明 ?</span>
      </div>

      <div v-if="isMultiItem">
        <div
          v-for="(item, index) in orderItems"
          :key="index"
          class="prod-row multi-prod-row"
        >
          <div class="p-img-box small-img-box">
            <img :src="getImageUrl(item.image)" class="p-img" alt="product">
          </div>
          <div class="p-info multi-p-info">
            <div class="p-title one-line">{{ item.name }}</div>
            <div class="p-count-row">
              <span class="orange-price">￥{{ item.price }}</span>
              <span class="item-count">x{{ item.quantity || 1 }}</span>
            </div>
          </div>
        </div>
        <div class="multi-summary">
          共 {{ orderItems.length }} 件商品，合计
          <span>￥{{ order.money }}</span>
        </div>
      </div>

      <div v-else class="prod-row">
        <div class="p-img-box">
          <img :src="productImage" class="p-img" alt="product">
          <div class="p-img-tag">正品保真 | 现货秒发</div>
        </div>
        <div class="p-info">
          <div class="p-title">
            <span class="tag-black">次日达</span>
            {{ productName }}
          </div>
          <div class="p-count-row">x1</div>
        </div>
      </div>
    </div>

    <div v-if="!isPaid && !isExpired" class="floating-bar">
      <button class="float-btn" @click="handleCardShare" :disabled="isBindingWechatShare">
        <span class="float-icon">⇪</span>
        分享
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
      <div class="poster-top-bar"></div>
      <div class="poster-tb-logo">淘宝代付</div>

      <div class="poster-price-block">
        <div class="poster-price"><small>￥</small>{{ order.money }}</div>
        <div class="poster-price-label">待支付金额</div>
      </div>

      <div v-if="isMultiItem" class="poster-prod-list">
        <div v-for="(item, index) in orderItems.slice(0, 3)" :key="index" class="poster-prod-row">
          <img :src="getImageUrl(item.image)" alt="product" crossorigin="anonymous">
          <div>{{ item.name }}</div>
        </div>
        <div v-if="orderItems.length > 3" class="poster-more">...等 {{ orderItems.length }} 件商品</div>
      </div>

      <div v-else class="poster-prod-single">
        <img :src="productImage" alt="product" crossorigin="anonymous">
        <div>{{ productName }}</div>
      </div>

      <div class="poster-qr-wrap">
        <div ref="posterQrContainer" class="poster-qr"></div>
        <div class="poster-qr-tip">长按识别二维码帮 TA 付款</div>
      </div>
    </div>
  </div>
</template>

<script>
import shareTemplateMixin from './shareTemplateMixin'

export default {
  name: 'CashierTaobao',
  mixins: [shareTemplateMixin],
  computed: {
    pageTitle() {
      return '淘宝代付'
    }
  },
  methods: {
    getDefaultShareTitle() {
      return '淘宝好物分享'
    },
    getDefaultShareDesc() {
      return '我在淘宝看中了这些宝贝，帮我付一下吧，下次请你吃饭。'
    },
    getPosterQrOptions() {
      return {
        width: 140,
        height: 140,
        colorLight: '#ffffff',
        colorDark: '#000000'
      }
    }
  }
}
</script>

<style scoped>
* { box-sizing: border-box; }

.cashier-taobao {
  min-height: 100vh;
  padding-bottom: 24px;
  background: #f1f1f1;
  font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Helvetica Neue", Arial, sans-serif;
}

.nav-bar {
  height: 44px;
  padding: 10px 15px;
  background: #f1f1f1;
}

.price-section {
  padding: 30px 0 20px;
  text-align: center;
  color: #FF5000;
}

.price-symbol {
  margin-right: 2px;
  font-size: 24px;
  font-weight: bold;
}

.price-val {
  font-family: Arial, sans-serif;
  font-size: 46px;
  font-weight: bold;
}

.main-card {
  margin: 0 12px;
  padding: 15px;
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}

.card-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 15px;
  color: #333;
  font-size: 14px;
  font-weight: 500;
}

.help-tips {
  display: flex;
  align-items: center;
  color: #999;
  font-size: 12px;
}

.prod-row {
  display: flex;
  align-items: flex-start;
}

.multi-prod-row {
  margin-bottom: 15px;
  padding-bottom: 10px;
  border-bottom: 1px dashed #f9f9f9;
}

.p-img-box {
  position: relative;
  width: 85px;
  height: 85px;
  flex-shrink: 0;
  margin-right: 12px;
  overflow: hidden;
  border-radius: 6px;
  background: #f8f8f8;
}

.small-img-box {
  width: 60px;
  height: 60px;
}

.p-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.p-img-tag {
  position: absolute;
  bottom: 0;
  left: -10%;
  width: 120%;
  padding: 2px 0;
  background: rgba(0, 0, 0, 0.7);
  color: #fff;
  font-size: 10px;
  text-align: center;
  transform: scale(0.85);
  white-space: nowrap;
}

.p-info {
  flex: 1;
  display: flex;
  height: 85px;
  min-width: 0;
  flex-direction: column;
  justify-content: flex-start;
}

.multi-p-info {
  height: auto;
  justify-content: center;
}

.p-title {
  overflow: hidden;
  color: #333;
  font-size: 14px;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.one-line {
  -webkit-line-clamp: 1;
}

.tag-black {
  display: inline-block;
  margin-right: 4px;
  padding: 0 4px;
  border-radius: 2px;
  background: #000;
  color: #d4b178;
  font-size: 10px;
  font-weight: normal;
  line-height: 14px;
  vertical-align: 1px;
}

.p-count-row {
  margin-top: 6px;
  color: #999;
  font-size: 14px;
}

.orange-price {
  color: #FF5000;
  font-weight: bold;
}

.item-count {
  margin-left: 5px;
  color: #999;
  font-size: 12px;
}

.multi-summary {
  padding-top: 5px;
  text-align: right;
  color: #999;
  font-size: 12px;
}

.multi-summary span {
  color: #333;
  font-weight: bold;
}

.floating-bar {
  position: fixed;
  right: 15px;
  bottom: 120px;
  z-index: 900;
  display: flex;
  flex-direction: column;
  gap: 15px;
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
  color: #FF5000;
  font-size: 20px;
}

.poster-modal,
.share-preparing-modal {
  position: fixed;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.poster-modal {
  z-index: 9999;
  flex-direction: column;
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
  z-index: 10001;
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
  border: 3px solid #ffe0cc;
  border-top-color: #FF5000;
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
  overflow: hidden;
  padding-bottom: 20px;
  border-radius: 16px;
  background: #fff;
}

.poster-top-bar {
  height: 10px;
  background: linear-gradient(90deg, #FF9000 0%, #FF5000 100%);
}

.poster-tb-logo {
  display: flex;
  align-items: center;
  justify-content: center;
  padding-top: 20px;
  color: #FF5000;
  font-size: 18px;
  font-weight: bold;
}

.poster-price-block {
  padding: 20px 0 10px;
  text-align: center;
}

.poster-price {
  color: #FF5000;
  font-size: 40px;
  font-weight: bold;
}

.poster-price small {
  font-size: 16px;
}

.poster-price-label {
  color: #999;
  font-size: 12px;
}

.poster-prod-list,
.poster-prod-single {
  margin: 15px;
  padding: 15px;
  border-radius: 8px;
  background: #f9f9f9;
}

.poster-prod-row,
.poster-prod-single {
  display: flex;
  align-items: center;
}

.poster-prod-row {
  margin-bottom: 10px;
}

.poster-prod-row img,
.poster-prod-single img {
  width: 40px;
  height: 40px;
  margin-right: 10px;
  border-radius: 4px;
  object-fit: cover;
}

.poster-prod-single img {
  width: 60px;
  height: 60px;
}

.poster-prod-row div,
.poster-prod-single div {
  flex: 1;
  overflow: hidden;
  color: #333;
  font-size: 13px;
  font-weight: bold;
  line-height: 1.4;
  text-overflow: ellipsis;
}

.poster-prod-row div {
  white-space: nowrap;
  font-weight: normal;
  font-size: 12px;
}

.poster-more {
  color: #999;
  font-size: 11px;
  text-align: center;
}

.poster-qr-wrap {
  padding: 10px;
  text-align: center;
}

.poster-qr {
  display: flex;
  width: 150px;
  min-height: 150px;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
  padding: 5px;
  border-radius: 4px;
  background: #fff;
}

.poster-qr-tip {
  margin-top: 8px;
  color: #999;
  font-size: 11px;
}

@keyframes sharePreparingSpin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
