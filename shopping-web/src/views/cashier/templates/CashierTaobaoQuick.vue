<template>
  <div class="cashier-taobao-quick">
    <div class="tb-header">
      <div class="avatar-box">
        <img :src="userAvatar" class="u-avatar" alt="avatar">
      </div>
      <div class="slogan">万水千山总是情，帮我付款行不行</div>
    </div>

    <div class="ticket-wrapper">
      <div class="card-top">
        <div class="price-label"><span class="price-symbol">￥</span>{{ order.money }}</div>
        <div v-if="isPaid" class="timer-text success">支付已完成</div>
        <div v-else-if="isExpired" class="timer-text">订单已超时关闭</div>
        <div v-else class="timer-text">
          请在 <span class="timer-num">{{ minutes }}</span> : <span class="timer-num">{{ seconds }}</span> 内完成支付，超时将自动取消订单
        </div>
      </div>

      <div class="tear-line"></div>

      <div class="card-bottom">
        <div class="shop-name">{{ shopName }}</div>
        <div v-if="isMultiItem">
          <div v-for="(item, index) in orderItems" :key="index" class="prod-row multi-prod">
            <img :src="getImageUrl(item.image)" class="prod-img" alt="product">
            <div class="prod-info">
              <div class="prod-title">{{ item.name }}</div>
              <div class="prod-qty price-text">￥{{ item.price }} <span>x{{ item.quantity || 1 }}</span></div>
            </div>
          </div>
        </div>
        <div v-else class="prod-row">
          <img :src="productImage" class="prod-img" alt="product">
          <div class="prod-info">
            <div class="prod-title">{{ productName }}</div>
            <div class="prod-qty">x1</div>
          </div>
        </div>
      </div>
    </div>

    <div class="footer-note">本页面仅用于分享代付请求和生成海报</div>

    <div v-if="!isPaid && !isExpired" class="admin-btns">
      <button class="btn-link" @click="handleGeneratePoster" :disabled="isGeneratingPoster">生成海报</button>
      <button class="btn-link green" @click="handleCardShare" :disabled="isBindingWechatShare">发送给好友</button>
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
      <div class="poster-bg">
        <img
          v-if="!posterBgLoadFailed"
          src="/tbhb.png"
          class="poster-bg-image"
          alt="poster background"
          crossorigin="anonymous"
          @error="posterBgLoadFailed = true"
        >
        <template v-if="posterBgLoadFailed">
          <div class="poster-fallback-title">淘宝代付</div>
          <div class="poster-fallback-price">¥{{ order.money }}</div>
        </template>
        <div ref="posterQrContainer" class="poster-qr-box"></div>
      </div>
    </div>
  </div>
</template>

<script>
import shareTemplateMixin from './shareTemplateMixin'

export default {
  name: 'CashierTaobaoQuick',
  mixins: [shareTemplateMixin],
  data() {
    return {
      posterBgLoadFailed: false
    }
  },
  computed: {
    pageTitle() {
      return '淘宝代付'
    },
    userAvatar() {
      if (this.userInfo?.avatar) {
        return this.getImageUrl(this.userInfo.avatar)
      }
      return 'https://img.yzcdn.cn/vant/cat.jpeg'
    },
    shopName() {
      if (this.orderItems.length > 0 && this.orderItems[0].shopName) {
        return this.orderItems[0].shopName
      }
      return this.order.shopName || this.order.shop_name || '淘宝店铺'
    }
  },
  methods: {
    getDefaultShareTitle() {
      return '请你帮我付个款吧~'
    },
    getDefaultShareDesc() {
      return '我正在使用淘宝闪购，请你帮我付个款吧~'
    },
    getPosterQrOptions() {
      return { width: 110, height: 110, colorLight: '#ffffff' }
    },
    getPosterCanvasOptions() {
      return { useCORS: true, scale: 2, allowTaint: false, backgroundColor: '#fff' }
    }
  }
}
</script>

<style scoped>
* { box-sizing: border-box; }
.cashier-taobao-quick { min-height: 100vh; padding-bottom: 32px; background: linear-gradient(180deg, #ff6b00 0, #ff8a00 180px, #f6f6f6 181px); }
.tb-header { padding: 32px 20px 24px; color: #fff; text-align: center; }
.avatar-box { display: block; margin-bottom: 10px; }
.u-avatar { width: 56px; height: 56px; border: 2px solid #fff; border-radius: 50%; object-fit: cover; }
.slogan { position: relative; display: inline-block; max-width: 90%; padding-bottom: 15px; font-size: 15px; font-weight: 500; }
.slogan::after { content: ''; position: absolute; bottom: 0; left: 50%; width: 140%; height: 1px; transform: translateX(-50%); background: linear-gradient(90deg, transparent, rgba(255,255,255,.35), transparent); }
.ticket-wrapper { margin: 0 16px; }
.card-top, .card-bottom { background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,.02); }
.card-top { padding: 35px 20px; border-radius: 12px 12px 0 0; text-align: center; }
.price-label { margin-bottom: 12px; color: #333; font-size: 42px; font-weight: bold; }
.price-symbol { margin-right: 2px; font-size: 26px; }
.timer-text { color: #999; font-size: 13px; line-height: 1.6; }
.timer-text.success { color: #29C378; font-weight: bold; }
.timer-num { color: #ff5000; font-weight: 700; }
.tear-line { height: 12px; background: radial-gradient(circle at 6px 6px, #f6f6f6 5px, #fff 6px) repeat-x; background-size: 16px 12px; }
.card-bottom { padding: 25px 20px; border-radius: 0 0 12px 12px; }
.shop-name { margin-bottom: 12px; color: #999; font-size: 13px; }
.prod-row { display: flex; align-items: flex-start; }
.multi-prod { margin-bottom: 15px; }
.prod-img { width: 68px; height: 68px; margin-right: 14px; border-radius: 6px; background: #f8f8f8; object-fit: cover; flex-shrink: 0; }
.prod-info { flex: 1; }
.prod-title { overflow: hidden; color: #333; font-size: 14px; font-weight: 500; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
.prod-qty { color: #999; font-size: 13px; }
.price-text { color: #FF5000; font-weight: bold; }
.price-text span { margin-left: 5px; color: #999; font-weight: normal; }
.footer-note { margin: 16px 20px; color: #aaa; font-size: 12px; text-align: center; }
.admin-btns { display: flex; gap: 12px; margin: 0 16px; }
.btn-link { flex: 1; height: 42px; border: 1px solid #FF5000; border-radius: 21px; background: #fff; color: #FF5000; font-weight: 700; }
.btn-link.green { border-color: #09BB07; color: #09BB07; }
.poster-modal, .share-preparing-modal { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; }
.poster-modal { z-index: 9999; flex-direction: column; background: rgba(0,0,0,.85); }
.poster-modal-close { position: absolute; top: 20px; right: 20px; color: #fff; font-size: 35px; }
.poster-image { width: 80%; max-width: 330px; border-radius: 12px; }
.poster-tip { margin-top: 15px; color: #fff; font-size: 14px; }
.share-preparing-modal { z-index: 10001; background: rgba(0,0,0,.45); }
.share-preparing-card { width: min(100%, 280px); padding: 28px 24px; border-radius: 16px; background: #fff; text-align: center; }
.share-preparing-spinner { width: 40px; height: 40px; margin: 0 auto 16px; border: 3px solid #ffe1cc; border-top-color: #FF5000; border-radius: 50%; animation: sharePreparingSpin .9s linear infinite; }
.share-preparing-title { margin-bottom: 8px; font-size: 16px; font-weight: 600; }
.share-preparing-text { color: #8a8f99; font-size: 13px; line-height: 1.6; }
.poster-canvas { position: fixed; left: -9999px; top: 0; width: 375px; overflow: hidden; border-radius: 16px; background: #fff; }
.poster-bg { position: relative; height: 560px; background: linear-gradient(180deg, #FF9000 0, #FF5000 14px, #fff 14px); }
.poster-bg-image { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.poster-fallback-title { position: absolute; top: 36px; left: 0; right: 0; color: #FF5000; font-size: 22px; font-weight: bold; text-align: center; }
.poster-fallback-price { position: absolute; top: 96px; left: 0; right: 0; color: #FF5000; font-size: 42px; font-weight: bold; text-align: center; }
.poster-bg::before { content: '淘宝代付'; position: absolute; left: 0; right: 0; top: 36px; text-align: center; color: #FF5000; font-size: 22px; font-weight: bold; }
.poster-bg::after { content: '￥' attr(data-money); }
.poster-bg::before,
.poster-bg::after { display: none; }
.poster-qr-box { position: absolute; right: 30px; bottom: 30px; z-index: 2; padding: 5px; border-radius: 5px; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
@keyframes sharePreparingSpin { from { transform: rotate(0); } to { transform: rotate(360deg); } }
</style>
