<template>
  <div class="cashier-ctrip-hotel">
    <div class="ctrip-header">
      <div class="header-nav">
        <span class="home-icon">⌂</span>
        <span>他人代付</span>
      </div>
    </div>

    <div class="main-card">
      <div class="avatar-wrap">
        <img :src="userAvatar" alt="avatar">
      </div>

      <div class="invite-title">{{ userNick }} 发起的代付邀请</div>
      <div class="invite-desc">{{ shareDescText }}</div>

      <div v-if="isMultiItem" class="product-list">
        <div v-for="(item, index) in orderItems" :key="index" class="hotel-row">
          <img :src="getImageUrl(item.image)" class="hotel-img" alt="hotel">
          <div class="hotel-info">
            <div class="hotel-name">{{ item.name }}</div>
            <div class="hotel-detail">￥{{ item.price }} x {{ item.quantity || 1 }}</div>
          </div>
        </div>
        <div class="multi-summary">
          共 {{ orderItems.length }} 件，总额 <span>￥{{ order.money }}</span>
        </div>
      </div>

      <div v-else class="prod-box">
        <img :src="productImage" class="prod-img" alt="hotel">
        <div class="prod-info">
          <div class="prod-name">{{ productName }}</div>
          <div class="prod-detail">1天 x1</div>
          <div class="prod-detail">{{ shopName }}</div>
        </div>
      </div>

      <div class="divider"></div>

      <div class="pay-area">
        <div class="price-row">
          <span class="price-tag">{{ isPaid ? '已支付' : (isExpired ? '已过期' : '待支付') }}</span>
          <span class="price-symbol">￥</span>
          <span class="price-val">{{ order.money }}</span>
        </div>
        <div v-if="!isPaid && !isExpired" class="timer-row">
          支付倒计时
          <span class="timer-box">{{ minutes }}</span> :
          <span class="timer-box">{{ seconds }}</span>
        </div>
        <button
          class="btn-pay"
          type="button"
          :disabled="isPaid || isExpired || isBindingWechatShare"
          @click="handleCardShare"
        >
          帮ta付款
        </button>
      </div>

      <div class="notice-section">
        <div class="notice-title">代付说明</div>
        <div>1. 代付订单创建后30分钟内未付款，订单会自动取消，你可以重新下单。</div>
        <div>2. 当代付订单退款成功后，实付金额将原路退还代付人。</div>
      </div>

      <div v-if="!isPaid && !isExpired" class="manager-tools">
        <button class="tool-btn poster-btn" @click="handleGeneratePoster" :disabled="isGeneratingPoster">
          {{ isGeneratingPoster ? '生成中...' : '生成分享海报' }}
        </button>
        <button class="tool-btn share-btn" @click="handleCardShare" :disabled="isBindingWechatShare">
          微信卡片分享
        </button>
      </div>
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
      <div class="ctrip-header poster-head">
        <div class="poster-head-title">携程酒店</div>
        <div>他人代付邀请</div>
      </div>
      <div class="main-card poster-main">
        <div class="avatar-wrap">
          <img :src="userAvatar" alt="avatar" crossorigin="anonymous">
        </div>
        <div class="invite-title">{{ userNick }}</div>
        <div class="invite-desc">{{ shareDescText }}</div>
        <div class="price-row">
          <span class="price-symbol">￥</span>
          <span class="price-val">{{ order.money }}</span>
        </div>
        <div class="poster-product">{{ productName }}</div>
        <div ref="posterQrContainer" class="poster-qr"></div>
        <div class="poster-qr-tip">长按识别二维码代付</div>
      </div>
    </div>
  </div>
</template>

<script>
import shareTemplateMixin from './shareTemplateMixin'

export default {
  name: 'CashierCtripHotel',
  mixins: [shareTemplateMixin],
  computed: {
    pageTitle() {
      return '携程酒店'
    },
    userNick() {
      return this.userInfo?.nickname || '携程用户'
    },
    userAvatar() {
      if (this.userInfo?.avatar) return this.getImageUrl(this.userInfo.avatar)
      return 'https://img.yzcdn.cn/vant/cat.jpeg'
    },
    shopName() {
      return this.order.shopName || this.order.shop_name || '携程精选'
    },
    shareDescText() {
      return this.shareCardConfig?.shareDesc || '我选好了商品，你来买单吧~'
    }
  },
  mounted() {
    this.fetchShareCardConfig()
  },
  methods: {
    getDefaultShareTitle() {
      return '携程特惠酒店等你来'
    },
    getDefaultShareDesc() {
      return '亲爱的朋友，帮我完成这趟旅程吧'
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
      return { useCORS: true, scale: 2, allowTaint: false, backgroundColor: '#f1f3f5' }
    }
  }
}
</script>

<style scoped>
* { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
.cashier-ctrip-hotel { min-height: 100vh; padding-bottom: 32px; background: #f1f3f5; color: #333; font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", sans-serif; }
.ctrip-header { position: relative; height: 180px; padding: 15px; background: #2dbb9a; color: #fff; text-align: center; }
.header-nav { position: relative; margin-top: 14px; font-size: 20px; font-weight: 700; }
.home-icon { position: absolute; left: 0; top: 0; font-size: 18px; }
.main-card { position: relative; z-index: 10; margin: -60px 15px 20px; padding: 0 0 20px; border-radius: 12px; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,.05); }
.avatar-wrap { width: 70px; height: 70px; margin: -35px auto 10px; padding: 3px; overflow: hidden; border-radius: 50%; background: #fff; }
.avatar-wrap img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
.invite-title { margin-bottom: 5px; color: #333; font-size: 18px; font-weight: 700; text-align: center; }
.invite-desc { margin-bottom: 20px; color: #666; font-size: 13px; text-align: center; }
.product-list { padding: 0 20px 20px; }
.hotel-row { display: flex; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px dashed #f5f5f5; }
.prod-box { display: flex; padding: 0 20px 20px; }
.prod-img { width: 85px; height: 85px; margin-right: 15px; border-radius: 8px; object-fit: cover; }
.hotel-img { width: 60px; height: 60px; margin-right: 10px; border-radius: 4px; object-fit: cover; flex-shrink: 0; }
.prod-info, .hotel-info { flex: 1; display: flex; min-width: 0; flex-direction: column; justify-content: center; }
.prod-name { color: #333; font-size: 16px; font-weight: 700; }
.hotel-name { overflow: hidden; color: #333; font-size: 14px; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
.prod-detail, .hotel-detail { margin-top: 6px; color: #888; font-size: 13px; }
.multi-summary { color: #666; font-size: 12px; text-align: right; }
.multi-summary span { color: #f85e13; font-weight: 700; }
.divider { position: relative; height: 1px; margin: 0 5px; border-top: 1px dashed #eee; }
.divider::before, .divider::after { content: ""; position: absolute; top: -8px; width: 16px; height: 16px; border-radius: 50%; background: #f1f3f5; }
.divider::before { left: -15px; }
.divider::after { right: -15px; }
.pay-area { padding: 25px 20px; text-align: center; }
.price-row { display: flex; align-items: baseline; justify-content: center; margin-bottom: 15px; color: #333; font-size: 16px; }
.price-tag { margin-right: 5px; }
.price-symbol { margin-right: 3px; color: #f85e13; font-size: 18px; font-weight: 700; }
.price-val { color: #f85e13; font-family: Arial, sans-serif; font-size: 32px; font-weight: 700; }
.timer-row { display: flex; align-items: center; justify-content: center; margin-bottom: 20px; color: #666; font-size: 13px; }
.timer-box { margin: 0 3px; padding: 1px 4px; border-radius: 2px; background: #ff6b3d; color: #fff; font-family: monospace; }
.btn-pay { display: block; width: 100%; padding: 12px 0; border: none; border-radius: 25px; background: #2dbb9a; box-shadow: 0 4px 10px rgba(45,187,154,.2); color: #fff; font-size: 16px; font-weight: 700; text-align: center; }
.btn-pay:disabled { background: #ccc; box-shadow: none; }
.notice-section { padding: 0 20px; color: #888; font-size: 12px; line-height: 1.8; }
.notice-title { margin-bottom: 8px; color: #666; font-size: 13px; font-weight: 700; }
.manager-tools { padding: 20px 20px 0; text-align: center; }
.tool-btn { display: block; width: 100%; height: 42px; margin-top: 10px; border-radius: 22px; background: #fff; font-size: 14px; font-weight: 700; }
.poster-btn { border: 1px solid #2dbb9a; color: #2dbb9a; }
.share-btn { border: 1px solid #f85e13; color: #f85e13; }
.poster-modal, .share-preparing-modal { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; }
.poster-modal { z-index: 9999; flex-direction: column; background: rgba(0,0,0,.85); }
.poster-modal-close { position: absolute; top: 20px; right: 20px; color: #fff; font-size: 35px; }
.poster-image { width: 80%; max-width: 330px; border-radius: 12px; }
.poster-tip { margin-top: 15px; color: #fff; font-size: 14px; }
.share-preparing-modal { z-index: 10001; background: rgba(0,0,0,.45); }
.share-preparing-card { width: min(100%, 280px); padding: 28px 24px; border-radius: 16px; background: #fff; text-align: center; }
.share-preparing-spinner { width: 40px; height: 40px; margin: 0 auto 16px; border: 3px solid rgba(45,187,154,.18); border-top-color: #2dbb9a; border-radius: 50%; animation: sharePreparingSpin .9s linear infinite; }
.share-preparing-title { margin-bottom: 8px; font-size: 16px; font-weight: 600; }
.share-preparing-text { color: #8a8f99; font-size: 13px; line-height: 1.6; }
.poster-canvas { position: fixed; left: -9999px; top: 0; z-index: 1; width: 375px; padding-bottom: 30px; background: #f1f3f5; }
.poster-head { height: 150px; padding-top: 40px; text-align: center; }
.poster-head-title { margin-bottom: 8px; font-size: 22px; font-weight: 700; }
.poster-main { margin-top: -50px; text-align: center; }
.poster-product { margin: 0 20px 12px; padding: 10px; border-radius: 6px; background: #f9f9f9; color: #333; font-size: 14px; }
.poster-qr { display: flex; width: 150px; min-height: 150px; align-items: center; justify-content: center; margin: 0 auto; padding: 5px; background: #fff; }
.poster-qr-tip { margin-top: 8px; color: #999; font-size: 12px; }
@keyframes sharePreparingSpin { from { transform: rotate(0); } to { transform: rotate(360deg); } }
</style>
