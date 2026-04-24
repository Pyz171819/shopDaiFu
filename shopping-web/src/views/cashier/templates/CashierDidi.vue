<template>
  <div class="cashier-didi">
    <div class="status-bar">
      <span class="back-mark">‹</span>
      {{ topStatus }}
    </div>

    <div class="white-card route-card-full">
      <div class="route-image-wrapper">
        <div class="route-map">
          <div class="route-line"></div>
          <div class="route-node node-start"></div>
          <div class="route-node node-end"></div>
          <div class="addr-text addr-start">当前位置</div>
          <div class="addr-text addr-end">前往 {{ destinationName }}</div>
        </div>
      </div>
    </div>

    <div class="white-card">
      <div class="driver-header">
        <div>
          <div class="prod-name">{{ productName }}</div>
          <div class="driver-info">
            周师傅 <span class="star-score">5.0</span> 1w+ 单
          </div>
        </div>

        <div class="driver-avatar-group">
          <div class="car-shape"></div>
          <img :src="driverAvatar" class="car-avatar" alt="driver">
        </div>
      </div>
      <div class="divider"></div>
      <div class="action-row">
        <div class="action-item">
          <div class="action-icon-circle icon-red">110</div>
          紧急
        </div>
        <div class="action-item">
          <div class="action-icon-circle">☎</div>
          打电话
        </div>
        <div class="action-item">
          <div class="action-icon-circle">i</div>
          商家帮助
        </div>
      </div>
    </div>

    <div class="white-card">
      <div class="bill-row">
        <div>行程费用</div>
        <div class="bill-val">{{ order.money }}元 <span class="chevron">⌄</span></div>
      </div>
      <div class="bill-row">
        <div>优惠券</div>
        <div class="bill-gray">无可用 <span class="chevron">›</span></div>
      </div>
      <div class="bill-row">
        <div>折上折优惠</div>
        <div class="bill-gray"><span class="bill-tag">暂无优惠</span> <span class="chevron">›</span></div>
      </div>

      <div class="total-price">
        {{ order.money }} <small>元</small>
      </div>

      <div class="bill-footer-tips">
        您正在为好友代付车费，取消订单后支付金额将原路退回。
      </div>

      <div class="divider"></div>

      <div class="pay-method-row">
        <span class="wx-icon">微信</span>
        微信支付
        <span class="check-icon">●</span>
      </div>
    </div>

    <div class="blue-shield-float">盾</div>

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
      <div class="poster-title">滴滴出行</div>
      <div class="status-bar poster-status">
        <span class="back-mark">‹</span>
        待支付
      </div>
      <div class="poster-pad">
        <div class="white-card route-card-full">
          <div class="route-image-wrapper">
            <div class="route-map">
              <div class="route-line"></div>
              <div class="route-node node-start"></div>
              <div class="route-node node-end"></div>
              <div class="addr-text addr-start">当前位置</div>
              <div class="addr-text addr-end">前往 {{ destinationName }}</div>
            </div>
          </div>
        </div>

        <div class="white-card poster-pay-card">
          <div class="poster-pay-title">代付车费</div>
          <div class="poster-price">{{ order.money }}<small>元</small></div>
          <div ref="posterQrContainer" class="poster-qr"></div>
          <div class="poster-qr-tip">长按识别二维码代付</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import shareTemplateMixin from './shareTemplateMixin'

export default {
  name: 'CashierDidi',
  mixins: [shareTemplateMixin],
  computed: {
    pageTitle() {
      return '滴滴打车'
    },
    destinationName() {
      if (this.orderItems.length > 0 && this.orderItems[0].shopName) {
        return this.orderItems[0].shopName
      }
      return this.order.shopName || this.order.shop_name || '目的地'
    },
    driverAvatar() {
      if (this.productImage) {
        return this.productImage
      }
      return 'https://img.yzcdn.cn/vant/cat.jpeg'
    },
    topStatus() {
      if (this.isPaid) {
        return '已支付'
      }
      if (this.isExpired) {
        return '已过期'
      }
      return '待支付'
    }
  },
  methods: {
    getDefaultShareTitle() {
      return '滴滴代付请求'
    },
    getDefaultShareDesc() {
      return '我正在使用滴滴打车，帮我付一下车费吧，谢谢。'
    },
    getPosterCanvasOptions() {
      return {
        useCORS: true,
        scale: 2,
        allowTaint: false,
        backgroundColor: '#F3F4F6'
      }
    }
  }
}
</script>

<style scoped>
* { box-sizing: border-box; }

.cashier-didi {
  min-height: 100vh;
  padding-bottom: 24px;
  background: #F3F4F6;
  color: #333;
}

.status-bar {
  display: flex;
  align-items: center;
  margin-bottom: 10px;
  padding: 15px 20px;
  background: #fff;
  color: #000;
  font-size: 20px;
  font-weight: bold;
}

.back-mark {
  margin-right: 10px;
  font-size: 28px;
  line-height: 1;
}

.white-card {
  position: relative;
  margin: 0 12px 12px;
  padding: 15px;
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}

.route-card-full {
  overflow: hidden;
  padding: 0;
}

.route-image-wrapper {
  position: relative;
  width: 100%;
}

.route-map {
  position: relative;
  height: 150px;
  background:
    linear-gradient(135deg, rgba(255, 128, 0, 0.12), transparent 40%),
    linear-gradient(45deg, transparent 48%, rgba(39, 74, 140, 0.08) 49%, rgba(39, 74, 140, 0.08) 51%, transparent 52%),
    #f8fafc;
}

.route-line {
  position: absolute;
  left: 16px;
  top: 42px;
  bottom: 42px;
  width: 2px;
  background: #d4d7dc;
}

.route-node {
  position: absolute;
  left: 12px;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #ccc;
  z-index: 2;
}

.node-start {
  top: 38px;
}

.node-end {
  bottom: 38px;
  background: #FF8000;
}

.addr-text {
  position: absolute;
  left: 32px;
  width: 80%;
  overflow: hidden;
  font-size: 13px;
  font-weight: 500;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.addr-start {
  top: 34px;
  color: #333;
}

.addr-end {
  bottom: 34px;
  color: #FF8000;
  font-weight: 600;
}

.driver-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 20px;
}

.prod-name {
  margin-bottom: 5px;
  color: #000;
  font-size: 18px;
  font-weight: bold;
}

.driver-info {
  display: flex;
  align-items: center;
  color: #999;
  font-size: 12px;
}

.star-score {
  margin: 0 5px;
  color: #FF8000;
  font-weight: bold;
}

.driver-avatar-group {
  position: relative;
  display: flex;
  justify-content: flex-end;
  width: 120px;
  height: 50px;
}

.car-shape {
  position: absolute;
  right: 38px;
  top: 20px;
  width: 62px;
  height: 22px;
  border-radius: 18px 26px 10px 10px;
  background: #274A8C;
}

.car-shape::before,
.car-shape::after {
  content: '';
  position: absolute;
  bottom: -4px;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #111;
}

.car-shape::before {
  left: 10px;
}

.car-shape::after {
  right: 10px;
}

.car-avatar {
  position: relative;
  z-index: 2;
  width: 50px;
  height: 50px;
  border: 2px solid #fff;
  border-radius: 50%;
  background: #fff;
  box-shadow: -2px 2px 5px rgba(0, 0, 0, 0.1);
  object-fit: cover;
}

.divider {
  border-top: 1px solid #f4f4f4;
  margin: 10px 0;
}

.action-row {
  display: flex;
  justify-content: space-around;
  padding-top: 10px;
  text-align: center;
}

.action-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  color: #666;
  font-size: 11px;
}

.action-icon-circle {
  display: flex;
  width: 32px;
  height: 32px;
  align-items: center;
  justify-content: center;
  margin-bottom: 6px;
  border: 1px solid #eee;
  border-radius: 50%;
  background: #fff;
  color: #555;
  font-size: 15px;
}

.icon-red {
  border-color: #FFE5E5;
  background: #FFF1F0;
  color: #FF4D4F;
  font-size: 11px;
}

.bill-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 0;
  border-bottom: 1px solid #f9f9f9;
  color: #000;
  font-size: 14px;
}

.bill-row:last-child {
  border-bottom: none;
}

.bill-val {
  font-family: Arial, sans-serif;
  font-size: 15px;
  font-weight: 500;
}

.bill-gray {
  display: flex;
  align-items: center;
  color: #999;
  font-size: 13px;
}

.bill-tag {
  margin-right: 5px;
  padding: 1px 4px;
  border-radius: 2px;
  background: #FF8000;
  color: #fff;
  font-size: 10px;
}

.chevron {
  color: #999;
  font-size: 12px;
}

.total-price {
  padding: 20px 0 10px;
  text-align: center;
  color: #000;
  font-family: Arial, sans-serif;
  font-size: 32px;
  font-weight: bold;
}

.total-price small {
  font-size: 16px;
}

.bill-footer-tips {
  margin-bottom: 10px;
  color: #999;
  font-size: 11px;
  text-align: center;
}

.pay-method-row {
  display: flex;
  align-items: center;
  font-size: 14px;
  font-weight: 500;
}

.wx-icon {
  margin-right: 8px;
  color: #09BB07;
  font-size: 14px;
  font-weight: bold;
}

.check-icon {
  margin-left: auto;
  color: #FF8000;
  opacity: 0.6;
}

.blue-shield-float {
  position: fixed;
  bottom: 100px;
  left: 15px;
  z-index: 90;
  display: flex;
  width: 30px;
  height: 30px;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  background: #274A8C;
  color: #fff;
  font-size: 12px;
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
  color: #FF8000;
  font-size: 18px;
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
  border: 3px solid #d8e0f0;
  border-top-color: #274A8C;
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
  background: #F3F4F6;
}

.poster-title {
  padding: 20px 0;
  background: #fff;
  text-align: center;
  font-size: 18px;
  font-weight: bold;
}

.poster-status {
  border: none;
  padding-top: 5px;
}

.poster-pad {
  padding: 0 20px 20px;
}

.poster-pay-card {
  text-align: center;
}

.poster-pay-title {
  margin-bottom: 10px;
  font-size: 18px;
  font-weight: bold;
}

.poster-price {
  color: #000;
  font-family: Arial, sans-serif;
  font-size: 40px;
  font-weight: bold;
}

.poster-price small {
  font-size: 16px;
}

.poster-qr {
  display: inline-block;
  margin-top: 20px;
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
