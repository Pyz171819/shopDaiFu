<template>
  <div class="cashier-jd">
    <div class="header-bg">
      <img
        src="/dingbu.png"
        class="bg-img"
        alt="bg"
        crossorigin="anonymous"
        @error="headerBgLoadFailed = true"
        v-if="!headerBgLoadFailed"
      >
      <div class="user-interaction">
        <img :src="userAvatar" class="u-avatar" alt="avatar">
        <div class="bubble">
          {{ shareTitleText }}
          <img
            src="https://img14.360buyimg.com/imagetools/jfs/t1/138657/16/22818/5048/61d563a3E090967a5/4e09525287310023.png"
            class="mascot"
            alt="jd"
            crossorigin="anonymous"
            @error="$event.target.style.display = 'none'"
          >
        </div>
      </div>
    </div>

    <div class="jd-card amount-card">
      <div class="amount-row">
        <span class="amount-label" :class="{ muted: isPaid }">代付金额</span>
        <span class="receiver-info">收货人：*{{ receiverName }}</span>
      </div>
      <div class="amount-bottom">
        <div class="price-large" :class="{ paid: isPaid }">¥{{ order.money }}</div>
        <div class="timer-row" :class="{ muted: isPaid }">
          <span class="timer-label">剩余支付时间</span>
          <div class="timer-box">
            <span class="t-num">00</span> :
            <span class="t-num">{{ minutes }}</span> :
            <span class="t-num">{{ seconds }}</span>
          </div>
        </div>
      </div>
      <img v-if="isPaid" :src="getPublicImageUrl('/yzf.png')" class="stamp-paid-img" alt="已支付">
      <img v-else-if="isExpired" :src="getPublicImageUrl('/guiqi.png')" class="stamp-paid-img stamp-expired" alt="已过期">
    </div>

    <div v-if="!isPaid" class="jd-card">
      <div class="pay-method-title">支付方式</div>
      <div class="pay-row">
        <div class="pay-left">
          <span class="wechat-icon">微信</span>
          <span class="pay-name">微信支付</span>
        </div>
        <span class="check-icon"></span>
      </div>
      <button
        class="btn-pay-now"
        :class="{ disabled: isExpired }"
        type="button"
        @click="handleCardShare"
        :disabled="isExpired || isBindingWechatShare"
      >
        {{ isExpired ? '订单已过期' : '立即支付' }}
      </button>
    </div>

    <div class="jd-card">
      <div class="order-title">代付订单信息</div>
      <div v-if="isMultiItem">
        <div v-for="(item, index) in orderItems" :key="index" class="prod-row multi-prod-row">
          <img :src="getImageUrl(item.image)" class="prod-img small-prod-img" alt="product">
          <div class="prod-info">
            <div class="prod-name one-line">{{ item.name }}</div>
            <div class="prod-price-row">
              <span class="prod-price">¥{{ item.price }}</span>
              <span class="prod-qty">x{{ item.quantity || 1 }}</span>
            </div>
          </div>
        </div>
        <div class="multi-summary">共 {{ orderItems.length }} 件商品，合计 ¥{{ order.money }}</div>
      </div>
      <div v-else class="prod-row">
        <img :src="productImage" class="prod-img" alt="product">
        <div class="prod-info">
          <div class="prod-name">{{ productName }}</div>
          <div class="prod-price-row">
            <span class="prod-price">¥{{ order.money }}</span>
            <span class="prod-qty">数量：1</span>
          </div>
        </div>
      </div>
    </div>

    <div v-if="!isPaid && !isExpired" class="admin-btns">
      <button class="admin-btn poster-btn" @click="handleGeneratePoster" :disabled="isGeneratingPoster">
        {{ isGeneratingPoster ? '生成中...' : '生成分享海报' }}
      </button>
      <button class="admin-btn share-btn" @click="handleCardShare" :disabled="isBindingWechatShare">
        发送给好友
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
      <div class="header-bg poster-header">
        <img
          src="/dingbu.png"
          class="bg-img"
          alt="bg"
          crossorigin="anonymous"
          @error="$event.target.style.display = 'none'"
        >
        <div class="user-interaction">
          <img :src="userAvatar" class="u-avatar" alt="avatar" crossorigin="anonymous">
          <div class="bubble">
            {{ shareTitleText }}
            <img
              src="https://img14.360buyimg.com/imagetools/jfs/t1/138657/16/22818/5048/61d563a3E090967a5/4e09525287310023.png"
              class="mascot"
              alt="jd"
              crossorigin="anonymous"
            >
          </div>
        </div>
      </div>

      <div class="jd-card poster-card">
        <div v-if="isMultiItem">
          <div v-for="(item, index) in orderItems.slice(0, 3)" :key="index" class="prod-row poster-prod-row">
            <img :src="getImageUrl(item.image)" class="prod-img poster-prod-img" alt="product" crossorigin="anonymous">
            <div class="prod-info">
              <div class="prod-name one-line">{{ item.name }}</div>
              <div class="prod-price">¥{{ item.price }}</div>
            </div>
          </div>
          <div v-if="orderItems.length > 3" class="poster-more">...等商品</div>
        </div>
        <div v-else class="prod-row">
          <img :src="productImage" class="prod-img" alt="product" crossorigin="anonymous">
          <div class="prod-info">
            <div class="prod-name poster-title">{{ productName }}</div>
            <div class="price-large poster-price">¥{{ order.money }}</div>
          </div>
        </div>
      </div>
      <div class="poster-qr-card">
        <div ref="posterQrContainer" class="poster-qr"></div>
        <div class="poster-qr-tip">长按识别二维码代付</div>
      </div>
    </div>
  </div>
</template>

<script>
import shareTemplateMixin from './shareTemplateMixin'

export default {
  name: 'CashierJingdong',
  mixins: [shareTemplateMixin],
  data() {
    return {
      headerBgLoadFailed: false
    }
  },
  computed: {
    pageTitle() {
      return '京东代付'
    },
    shareTitleText() {
      return this.shareCardConfig?.shareTitle || this.getDefaultShareTitle()
    },
    userAvatar() {
      if (this.userInfo?.avatar) {
        return this.getImageUrl(this.userInfo.avatar)
      }
      return 'https://img.yzcdn.cn/vant/cat.jpeg'
    },
    receiverName() {
      const name = this.userInfo?.nickname || '用户'
      return name.length > 1 ? name.slice(1) : name
    }
  },
  mounted() {
    this.fetchShareCardConfig()
  },
  methods: {
    getPublicImageUrl(path) {
      const publicPath = process.env.BASE_URL || '/'
      return publicPath + path.replace(/^\//, '')
    },
    getDefaultShareTitle() {
      return '我在京东挑了件好东西，请你帮我付款吧'
    },
    getDefaultShareDesc() {
      return this.productName || '精选好物，帮忙代付一下'
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
      return { useCORS: true, scale: 2, allowTaint: false, backgroundColor: '#f6f6f6' }
    }
  }
}
</script>

<style scoped>
* { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
.cashier-jd { min-height: 100vh; padding-bottom: 24px; background: #f6f6f6; font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", Helvetica, Arial, sans-serif; }
.header-bg { position: relative; min-height: 126px; margin-bottom: -20px; overflow: hidden; background: linear-gradient(105deg, #ff3131 0%, #ff7b38 100%); }
.bg-img { position: absolute; inset: 0; z-index: 1; width: 100%; height: 100%; object-fit: cover; }
.user-interaction { position: relative; z-index: 2; display: flex; align-items: flex-start; padding: 20px 15px 40px; }
.u-avatar { width: 50px; height: 50px; margin-top: 10px; margin-right: 12px; border: 2px solid #fff; border-radius: 50%; object-fit: cover; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
.bubble { position: relative; max-width: 300px; padding: 16px 88px 16px 18px; border-radius: 18px; background: rgba(255,255,255,.95); color: #d62010; font-size: 14px; font-weight: 700; line-height: 1.5; }
.mascot { position: absolute; right: -5px; bottom: -15px; width: 75px; pointer-events: none; filter: drop-shadow(0 2px 5px rgba(0,0,0,.1)); }
.jd-card { position: relative; z-index: 10; margin: 0 12px 12px; padding: 20px; border-radius: 12px; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,.03); overflow: hidden; }
.amount-row, .pay-row, .prod-price-row, .amount-bottom { display: flex; align-items: center; justify-content: space-between; }
.amount-label { color: #333; font-size: 16px; font-weight: 500; }
.amount-label.muted, .timer-row.muted { color: #bbb; }
.receiver-info { color: #666; font-size: 14px; }
.amount-bottom { align-items: flex-end; margin-top: 10px; }
.price-large { color: #f2270c; font-size: 36px; font-weight: 700; line-height: 1; letter-spacing: -1px; }
.price-large.paid { color: #bbb; font-size: 30px; font-weight: 400; }
.timer-row { display: flex; align-items: center; gap: 4px; color: #333; }
.timer-label { color: #999; font-size: 13px; }
.timer-box { display: flex; align-items: center; font-size: 13px; font-variant-numeric: tabular-nums; }
.t-num { min-width: 24px; margin: 0 3px; padding: 1px 5px; border: 1px solid #ddd; border-radius: 4px; background: #fff; text-align: center; font-family: monospace; }
.stamp-paid-img { position: absolute; right: 15px; top: 50%; z-index: 20; width: 100px; opacity: .9; transform: translateY(-60%) rotate(-15deg); pointer-events: none; }
.stamp-expired { filter: grayscale(100%); opacity: .6; }
.pay-method-title { margin-bottom: 15px; color: #666; font-size: 14px; }
.pay-left { display: flex; align-items: center; }
.wechat-icon { margin-right: 8px; color: #09bb07; font-weight: 700; }
.pay-name { color: #333; }
.check-icon { width: 14px; height: 14px; border-radius: 50%; background: #f75a4b; }
.btn-pay-now { display: block; width: 100%; margin-top: 15px; padding: 13px 0; border: none; border-radius: 25px; background: linear-gradient(90deg, #ff3100 0%, #f2270c 100%); box-shadow: 0 4px 12px rgba(242,39,12,.3); color: #fff; font-size: 17px; font-weight: 700; text-align: center; }
.btn-pay-now.disabled { background: #ccc; box-shadow: none; }
.order-title { margin-bottom: 15px; color: #333; font-size: 14px; font-weight: 700; }
.prod-row { display: flex; align-items: center; }
.multi-prod-row { margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px dashed #f5f5f5; }
.prod-img { width: 80px; height: 80px; margin-right: 15px; border: 1px solid #f0f0f0; border-radius: 6px; object-fit: cover; flex-shrink: 0; }
.small-prod-img { width: 60px; height: 60px; }
.prod-info { flex: 1; min-width: 0; }
.prod-name { overflow: hidden; color: #333; font-size: 14px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
.one-line { -webkit-line-clamp: 1; }
.prod-price { color: #f2270c; font-weight: 700; }
.prod-qty, .multi-summary, .poster-more { color: #999; font-size: 12px; }
.multi-summary { text-align: right; }
.admin-btns { padding: 0 15px 20px; }
.admin-btn { width: 100%; height: 46px; margin-bottom: 10px; border-radius: 23px; background: #fff; font-size: 15px; font-weight: 700; }
.poster-btn { border: 1px solid #f2270c; color: #f2270c; }
.share-btn { border: 1px solid #09bb07; color: #09bb07; }
.poster-modal, .share-preparing-modal { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; }
.poster-modal { z-index: 9999; flex-direction: column; background: rgba(0,0,0,.85); }
.poster-modal-close { position: absolute; top: 20px; right: 20px; color: #fff; font-size: 35px; }
.poster-image { width: 80%; max-width: 330px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,.5); }
.poster-tip { margin-top: 15px; color: #fff; font-size: 14px; }
.share-preparing-modal { z-index: 10001; background: rgba(0,0,0,.45); }
.share-preparing-card { width: min(100%, 280px); padding: 28px 24px; border-radius: 16px; background: #fff; text-align: center; }
.share-preparing-spinner { width: 40px; height: 40px; margin: 0 auto 16px; border: 3px solid #ffd5d0; border-top-color: #f2270c; border-radius: 50%; animation: sharePreparingSpin .9s linear infinite; }
.share-preparing-title { margin-bottom: 8px; font-size: 16px; font-weight: 600; }
.share-preparing-text { color: #8a8f99; font-size: 13px; line-height: 1.6; }
.poster-canvas { position: fixed; left: -9999px; top: 0; z-index: 1; width: 375px; padding-bottom: 30px; background: #f6f6f6; }
.poster-header { min-height: 126px; }
.poster-card { margin-top: -10px; }
.poster-prod-row { margin-bottom: 8px; }
.poster-prod-img { width: 40px; height: 40px; margin-right: 10px; }
.poster-title { font-weight: 700; }
.poster-price { margin-top: 10px; font-size: 24px; }
.poster-qr-card { margin: 12px; padding: 20px; border-radius: 12px; background: #fff; text-align: center; }
.poster-qr {
  display: flex;
  width: 150px;
  min-height: 150px;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
  padding: 5px;
  background: #fff;
  border-radius: 4px;
}
.poster-qr-tip { margin-top: 10px; color: #999; font-size: 12px; }
@media (min-width: 768px) {
  .header-bg { min-height: 110px; }
}
@keyframes sharePreparingSpin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>
