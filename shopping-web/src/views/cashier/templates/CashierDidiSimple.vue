<template>
  <div class="cashier-didi-simple">
    <div class="didi-header">
      滴滴代付
      <span>安全快捷的代付请求</span>
    </div>

    <div class="main-card">
      <div class="pay-label" :class="{ muted: isExpired || isPaid }">
        {{ isExpired ? '订单已失效' : '需付款' }}
      </div>
      <div class="pay-price">{{ order.money }}<span>元</span></div>

      <div class="notice-box">
        <div class="notice-title">请您知悉：</div>
        <div class="notice-item"><span class="notice-num">1.</span>付款前请务必确认好友身份，避免诈骗。</div>
        <div class="notice-item"><span class="notice-num">2.</span>如行程取消，款项将原路退回付款账户。</div>
        <div class="notice-item"><span class="notice-num">3.</span>本页面仅用于生成代付分享和海报。</div>
      </div>
    </div>

    <div v-if="!isPaid && !isExpired" class="manager-tools">
      <button class="tool-btn poster-btn" @click="handleGeneratePoster" :disabled="isGeneratingPoster">
        {{ isGeneratingPoster ? '生成中...' : '生成海报' }}
      </button>
      <button class="tool-btn share-btn" @click="handleCardShare" :disabled="isBindingWechatShare">
        {{ isBindingWechatShare ? '准备中...' : '发送给好友' }}
      </button>
    </div>

    <div class="footer-info">滴滴出行 · 让每一次出发更安心</div>

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
      <div class="didi-header poster-title">滴滴代付</div>
      <div class="main-card poster-main-card">
        <div class="pay-label">{{ posterShareTitle }}</div>
        <div class="pay-price">{{ order.money }}<span>元</span></div>
        <div class="notice-box">
          <div class="notice-title">请您知悉</div>
          <div class="notice-item">{{ posterShareDesc }}</div>
        </div>
        <div class="poster-qr-block">
          <div ref="posterQrContainer" class="poster-qr"></div>
          <div class="poster-qr-tip">长按识别二维码代付</div>
        </div>
        <div class="poster-qr-tip">长按识别二维码代付</div>
      </div>
    </div>
  </div>
</template>

<script>
import shareTemplateMixin from './shareTemplateMixin'

export default {
  name: 'CashierDidiSimple',
  mixins: [shareTemplateMixin],
  computed: {
    pageTitle() {
      return '滴滴代付'
    },
    posterShareTitle() {
      return this.shareCardConfig?.shareTitle || this.getDefaultShareTitle()
    },
    posterShareDesc() {
      return this.shareCardConfig?.shareDesc || this.getDefaultShareDesc()
    }
  },
  methods: {
    getDefaultShareTitle() {
      return '滴滴快车优惠券'
    },
    getDefaultShareDesc() {
      return '亲爱的，帮我付个车费，让我平安到达目的地。'
    },
    getPosterQrOptions() {
      return {
        width: 140,
        height: 140,
        colorDark: '#29C378',
        colorLight: '#ffffff'
      }
    },
    getPosterCanvasOptions() {
      return { useCORS: true, scale: 2, allowTaint: false, backgroundColor: '#ffffff' }
    }
  }
}
</script>

<style scoped>
* { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
.cashier-didi-simple {
  min-height: 100vh;
  padding: 12px 0 28px;
  background: #f3f4f5;
  color: #222;
  font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Helvetica Neue", Arial, sans-serif;
}
.cashier-didi-simple > .didi-header { display: none; }
.didi-header {
  background: #fff;
  text-align: center;
  padding: 12px 0;
  font-size: 17px;
  font-weight: 500;
  color: #000;
  border-bottom: 1px solid #eee;
}
.didi-header span { display: block; margin-top: 2px; color: #999; font-size: 12px; font-weight: 400; }
.main-card {
  margin: 0 12px;
  padding: 40px 20px 30px;
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 2px 8px rgba(0,0,0,.02);
  text-align: center;
  overflow: hidden;
}
.pay-label { margin-bottom: 15px; color: #333; font-size: 16px; font-weight: 500; }
.pay-label.muted { color: #999; }
.pay-price {
  margin-bottom: 40px;
  color: #000;
  font-family: Arial, sans-serif;
  font-size: 40px;
  font-weight: 500;
  letter-spacing: -.5px;
}
.pay-price span { margin-left: 2px; font-size: 18px; font-weight: 400; }
.notice-box {
  margin-bottom: 30px;
  padding: 20px;
  border-radius: 8px;
  background: #f7f8fa;
  text-align: left;
}
.notice-title { margin-bottom: 10px; color: #999; font-size: 14px; font-weight: 400; }
.notice-item { display: flex; margin-bottom: 8px; color: #999; font-size: 13px; line-height: 1.6; }
.notice-num { margin-right: 4px; color: #999; font-weight: 400; }
.manager-tools {
  display: flex;
  gap: 8px;
  margin: 0 12px;
  padding: 20px 20px 30px;
  border-top: 1px dashed #eee;
  border-radius: 0 0 12px 12px;
  background: #fff;
}
.main-card + .manager-tools { margin-top: -30px; }
.tool-btn {
  flex: 1;
  height: 42px;
  border-radius: 21px;
  border: 1px solid #29c378;
  background: #fff;
  color: #29c378;
  font-size: 14px;
  font-weight: 700;
  box-shadow: none;
}
.poster-btn {
  background: #29c378;
  color: #fff;
  box-shadow: 0 4px 10px rgba(41, 195, 120, .2);
}
.tool-btn:disabled { opacity: .65; }
.footer-info { margin-top: 20px; color: #ccc; text-align: center; font-size: 12px; }
.poster-modal, .share-preparing-modal { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; }
.poster-modal { z-index: 9999; flex-direction: column; background: rgba(0,0,0,.8); }
.poster-modal-close { position: absolute; top: 20px; right: 20px; color: #fff; font-size: 35px; }
.poster-image { width: 85%; max-width: 320px; border-radius: 10px; }
.poster-tip { margin-top: 15px; color: #fff; font-size: 14px; }
.share-preparing-modal { z-index: 10001; background: rgba(0,0,0,.45); }
.share-preparing-card { width: min(100%, 280px); padding: 28px 24px; border-radius: 16px; background: #fff; text-align: center; }
.share-preparing-spinner { width: 40px; height: 40px; margin: 0 auto 16px; border: 3px solid rgba(41,195,120,.18); border-top-color: #29c378; border-radius: 50%; animation: sharePreparingSpin .9s linear infinite; }
.share-preparing-title { margin-bottom: 8px; font-size: 16px; font-weight: 600; }
.share-preparing-text { color: #8a8f99; font-size: 13px; line-height: 1.6; }
.poster-canvas { position: fixed; top: 0; left: -9999px; z-index: 1; width: 375px; padding-bottom: 30px; background: #fff; }
.poster-title { display: block; margin: 0; padding: 12px 0; border-bottom: 1px solid #eee; }
.poster-main-card { margin: 12px; padding: 40px 20px 30px; border: 1px solid #eee; border-radius: 12px; box-shadow: none; }
.poster-main-card .pay-label { margin-bottom: 15px; color: #333; }
.poster-main-card .pay-price { margin-bottom: 40px; }
.poster-main-card .notice-box { margin: 0 -8px 30px; padding: 18px 14px; }
.poster-main-card .notice-item {
  display: block;
  white-space: nowrap;
  font-size: 12px;
  letter-spacing: -0.2px;
}
.poster-main-card > .poster-qr-tip { display: none; }
.poster-qr-block { padding-top: 10px; text-align: center; }
.poster-qr {
  display: flex;
  width: 152px;
  min-height: 152px;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
  padding: 6px;
  background: #fff;
}
.poster-qr-tip { margin-top: 10px; color: #999; font-size: 12px; }
@media (min-width: 768px) {
  .cashier-didi-simple { padding-top: 16px; }
}
@keyframes sharePreparingSpin { from { transform: rotate(0); } to { transform: rotate(360deg); } }
</style>
