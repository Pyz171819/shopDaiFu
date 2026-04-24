<template>
  <div class="cashier-maoyan">
    <div class="heavy-red-card">
      <div class="timer-inner">
        <template v-if="isPaid">
          <span class="state-icon success">●</span> 订单已完成支付
        </template>
        <template v-else-if="isExpired">
          <span class="state-icon muted">●</span> 订单已关闭
        </template>
        <template v-else>
          <span class="state-icon">●</span> 等待您的付款
          <span class="time-str">00:{{ minutes }}:{{ seconds }}</span>
          后订单自动关闭
        </template>
      </div>
    </div>

    <div class="faint-red-card">
      <div v-if="isMultiItem">
        <div v-for="(item, index) in orderItems" :key="index" class="prod-content multi-prod">
          <img :src="getImageUrl(item.image)" class="p-img" alt="movie">
          <div class="p-info">
            <div class="p-title">{{ item.name }}</div>
            <div class="p-desc">【热卖限定包装】官方正品</div>
            <div class="p-meta-row">
              <span class="p-price">￥{{ item.price }}</span>
              <span class="p-count">{{ item.quantity || 1 }} 张</span>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="prod-content">
        <img :src="productImage" class="p-img" alt="movie">
        <div class="p-info">
          <div class="p-title">{{ productName }}</div>
          <div class="p-desc">【热卖限定包装】官方正品</div>
          <div class="p-meta-row">
            <span class="p-price">￥{{ order.money }}</span>
            <span class="p-count">1 张</span>
          </div>
        </div>
      </div>

      <div class="tag-row">
        <div class="tag-item red">不支持退票</div>
        <div class="tag-item green">限时改签</div>
        <div class="tag-detail">查看详情 ›</div>
      </div>
    </div>

    <div class="normal-card">
      <div class="card-title">订单优惠</div>
      <div class="list-row"><span class="list-label">影票活动与优惠券</span><span class="list-val">无可用</span></div>
      <div class="list-row"><span class="list-label">猫享卡</span><span class="list-val">无可用</span></div>
      <div class="list-row"><span class="list-label">观影卡</span><span class="list-val">无可用</span></div>
    </div>

    <div class="normal-card">
      <div class="card-title">手机号</div>
      <div class="sub-tips">手机号仅用于生成订单，取票码不再以短信发送</div>
    </div>

    <div class="normal-card">
      <div class="card-title">购票须知</div>
      <ul class="notice-list">
        <li>1. 请提前 30 分钟左右到达影院现场，通过影院自助取票机完成取票。</li>
        <li>2. 若取票过程中遇到问题，请联系影院工作人员处理。</li>
        <li>3. 请及时关注电影开场时间，凭票有序检票入场。</li>
      </ul>
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
      <div class="poster-top">
        <span class="ticket-mark">▣</span> 猫眼电影优惠券
      </div>
      <div class="poster-content">
        <div class="poster-price"><small>￥</small>{{ order.money }}</div>
        <div class="poster-prod">
          <img :src="productImage" alt="movie" crossorigin="anonymous">
          <div class="poster-prod-title">{{ productName }}</div>
        </div>
      </div>
      <div class="poster-footer">
        <div ref="posterQrContainer" class="poster-qr"></div>
        <div class="poster-qr-tip">长按识别二维码帮 TA 付款</div>
      </div>
    </div>
  </div>
</template>

<script>
import shareTemplateMixin from './shareTemplateMixin'

export default {
  name: 'CashierMaoyan',
  mixins: [shareTemplateMixin],
  computed: {
    pageTitle() {
      return '猫眼代付'
    },
    statusText() {
      if (this.isPaid) {
        return '订单已完成'
      }
      if (this.isExpired) {
        return '订单已过期'
      }
      return '订单未支付'
    }
  },
  methods: {
    getDefaultShareTitle() {
      return '猫眼电影优惠券'
    },
    getDefaultShareDesc() {
      return '精品电影，帮我付一下，我请你下次。'
    },
    getPosterQrOptions() {
      return {
        width: 140,
        height: 140,
        colorDark: '#000000',
        colorLight: '#ffffff'
      }
    },
    getPosterCanvasOptions() {
      return {
        useCORS: true,
        scale: 2,
        allowTaint: false,
        backgroundColor: null
      }
    }
  }
}
</script>

<style scoped>
* { box-sizing: border-box; }

.cashier-maoyan {
  min-height: 100vh;
  padding-bottom: 24px;
  background: #F5F5F5;
  font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", sans-serif;
}

.heavy-red-card,
.faint-red-card,
.normal-card {
  margin: 15px 12px;
  overflow: hidden;
  border-radius: 12px;
  background: #fff;
}

.heavy-red-card {
  border: 1px solid rgba(240, 61, 55, 0.2);
  box-shadow: 0 5px 35px rgba(240, 61, 55, 0.4);
}

.faint-red-card {
  border: 1px solid rgba(240, 61, 55, 0.05);
  box-shadow: 0 0 20px rgba(240, 61, 55, 0.12);
}

.normal-card {
  padding: 15px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.timer-inner {
  padding: 12px 0;
  text-align: center;
  color: #555;
  font-size: 13px;
  font-weight: 500;
}

.state-icon {
  margin-right: 6px;
  color: #F03D37;
}

.state-icon.success {
  color: #1BC57F;
}

.state-icon.muted {
  color: #999;
}

.time-str {
  color: #F03D37;
  font-weight: 700;
}

.prod-content {
  display: flex;
  padding: 15px 15px 0;
}

.multi-prod {
  padding-bottom: 12px;
  border-bottom: 1px dashed #f6f6f6;
}

.multi-prod:last-child {
  border-bottom: none;
}

.p-img {
  width: 75px;
  height: 75px;
  flex-shrink: 0;
  margin-right: 12px;
  border: 1px solid #f0f0f0;
  border-radius: 6px;
  object-fit: cover;
}

.p-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.p-title {
  overflow: hidden;
  color: #333;
  font-size: 16px;
  font-weight: bold;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.p-desc,
.p-count {
  color: #999;
  font-size: 12px;
}

.p-meta-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.p-price {
  color: #F03D37;
  font-size: 16px;
  font-weight: 700;
}

.tag-row {
  display: flex;
  align-items: center;
  margin-top: 15px;
  padding: 12px 15px;
  border-top: 1px dashed #f5f5f5;
  font-size: 12px;
}

.tag-item {
  margin-right: 15px;
}

.tag-item.red {
  color: #F03D37;
}

.tag-item.green {
  color: #1BC57F;
}

.tag-detail {
  margin-left: auto;
  color: #999;
}

.card-title {
  margin-bottom: 12px;
  color: #333;
  font-size: 16px;
  font-weight: bold;
}

.list-row {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  font-size: 14px;
}

.list-label {
  color: #333;
}

.list-val,
.sub-tips {
  color: #999;
}

.sub-tips,
.notice-list {
  font-size: 12px;
}

.notice-list {
  margin: 0;
  padding-left: 0;
  color: #666;
  line-height: 1.8;
  list-style: none;
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
  color: #F03D37;
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
  border: 3px solid #ffd9d7;
  border-top-color: #F03D37;
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
  border-radius: 16px;
  background-image: linear-gradient(to bottom, #ef4238 0%, #ef4238 60px, #fff 60px, #fff 100%);
}

.poster-top {
  display: flex;
  height: 60px;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 20px;
  font-weight: bold;
}

.ticket-mark {
  margin-right: 8px;
  font-size: 24px;
}

.poster-content {
  padding: 25px;
  text-align: center;
}

.poster-price {
  margin-bottom: 20px;
  color: #F03D37;
  font-size: 42px;
  font-weight: bold;
}

.poster-price small {
  font-size: 18px;
}

.poster-prod {
  display: flex;
  align-items: center;
  padding: 15px;
  border-radius: 8px;
  background: #f9f9f9;
  text-align: left;
}

.poster-prod img {
  width: 60px;
  height: 60px;
  margin-right: 12px;
  border-radius: 4px;
  object-fit: cover;
}

.poster-prod-title {
  flex: 1;
  color: #333;
  font-size: 14px;
  font-weight: bold;
  line-height: 1.4;
}

.poster-footer {
  padding: 20px;
  background: #f2f2f2;
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
  margin-top: 10px;
  color: #666;
  font-size: 12px;
}

@keyframes sharePreparingSpin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
