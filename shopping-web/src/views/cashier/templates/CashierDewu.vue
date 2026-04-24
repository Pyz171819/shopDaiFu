<template>
  <div class="cashier-dewu">
    <div class="top-icons">得物 App</div>
    <div class="header-bg"></div>
    <div class="decoration-layer">
      <div class="quote-overlay">“这款好物来自得物App，我超喜欢它，请你快来帮我付个款，谢啦！”</div>
    </div>

    <div class="unified-card">
      <div class="price-section">
        <div class="price-seal">{{ isPaid ? '已支付' : isExpired ? '已过期' : '待支付' }}</div>
        <div class="price-label">付款金额</div>
        <div class="price-amount"><span>￥</span>{{ order.money }}</div>
        <div class="timer-wrap">
          <template v-if="isPaid">订单已完成支付</template>
          <template v-else>剩余支付时间 <span class="timer-box">{{ minutes }}</span> : <span class="timer-box">{{ seconds }}</span></template>
        </div>
      </div>

      <div class="card-divider"></div>

      <div class="product-section">
        <div class="row-titles">
          <span>商品信息</span>
          <span>收货人：<span class="receiver-tag">{{ receiverName }}</span></span>
        </div>
        <div v-if="isMultiItem">
          <div v-for="(item, index) in orderItems" :key="index" class="product-item multi-item">
            <img :src="getImageUrl(item.image)" class="p-img small-img" alt="product">
            <div class="p-box">
              <div class="p-title one-line">{{ item.name }}</div>
              <div class="item-price">￥{{ item.price }}</div>
            </div>
          </div>
          <div class="multi-summary">共 {{ orderItems.length }} 件商品</div>
        </div>
        <div v-else class="product-item">
          <img :src="productImage" class="p-img" alt="product">
          <div class="p-box"><div class="p-title">{{ productName }}</div></div>
        </div>
      </div>

      <div v-if="!isPaid && !isExpired" class="share-actions">
        <button class="share-action" @click="handleCardShare" :disabled="isBindingWechatShare">卡片分享</button>
        <button class="share-action poster-action" @click="handleGeneratePoster" :disabled="isGeneratingPoster">生成海报</button>
      </div>
    </div>

    <div class="serrated-edge"></div>

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
      <div class="header-bg"></div>
      <div class="decoration-layer"><div class="quote-overlay">发现好物，帮我付个款吧~</div></div>
      <div class="unified-card poster-card">
        <div class="price-section">
          <div class="price-amount"><span>￥</span>{{ order.money }}</div>
        </div>
        <div class="card-divider"></div>
        <div class="product-section">
          <div v-if="isMultiItem">
            <div v-for="(item, index) in orderItems.slice(0, 3)" :key="index" class="product-item poster-product">
              <img :src="getImageUrl(item.image)" class="p-img poster-img" alt="product" crossorigin="anonymous">
              <div class="p-box">
                <div class="p-title one-line">{{ item.name }}</div>
                <div class="item-price">￥{{ item.price }}</div>
              </div>
            </div>
          </div>
          <div v-else class="product-item">
            <img :src="productImage" class="p-img" alt="product" crossorigin="anonymous">
            <div class="p-box"><div class="p-title">{{ productName }}</div></div>
          </div>
        </div>
        <div ref="posterQrContainer" class="poster-qr"></div>
        <div class="poster-qr-tip">长按识别二维码代付</div>
      </div>
      <div class="serrated-edge"></div>
    </div>
  </div>
</template>

<script>
import shareTemplateMixin from './shareTemplateMixin'

export default {
  name: 'CashierDewu',
  mixins: [shareTemplateMixin],
  computed: {
    pageTitle() {
      return '得物代付'
    },
    receiverName() {
      return this.userInfo?.nickname || '好友'
    }
  },
  methods: {
    getDefaultShareTitle() {
      return '得物限量版球鞋等你来'
    },
    getDefaultShareDesc() {
      return '帮我付一下，这双鞋子太酷了，我等不及想要拥有它！'
    },
    getPosterCanvasOptions() {
      return { useCORS: true, scale: 2, allowTaint: false, backgroundColor: '#F8F8F8' }
    }
  }
}
</script>

<style scoped>
* { box-sizing: border-box; }
.cashier-dewu { min-height: 100vh; padding-bottom: 32px; background: #F8F8F8; color: #111; }
.top-icons { padding: 14px 18px; color: #00C2C9; font-size: 13px; font-weight: 800; text-align: right; }
.header-bg { width: 100%; height: 115px; background: linear-gradient(135deg,#111,#252525); }
.decoration-layer { position: relative; margin-top: -42px; padding: 0 18px 20px; }
.quote-overlay { padding: 12px 14px; border-radius: 10px; background: rgba(255,255,255,.92); color: #111; font-size: 13px; line-height: 1.5; box-shadow: 0 8px 20px rgba(0,0,0,.08); }
.unified-card { position: relative; z-index: 10; margin: -9px 12px 0; padding-bottom: 25px; border-radius: 4px; background: #fff; box-shadow: 0 4px 15px rgba(0,0,0,.03); }
.price-section { position: relative; padding: 40px 20px 25px; text-align: center; }
.price-seal { position: absolute; top: 10px; right: 15px; padding: 5px 10px; border: 1px solid #00C2C9; border-radius: 999px; color: #00C2C9; font-size: 12px; transform: rotate(-12deg); }
.price-label { margin-bottom: 12px; color: #333; font-size: 16px; }
.price-amount { color: #000; font-size: 48px; font-weight: bold; }
.price-amount span { margin-right: 2px; font-size: 28px; }
.timer-wrap { margin-top: 8px; color: #999; font-size: 13px; }
.timer-box { padding: 2px 6px; border-radius: 3px; background: #111; color: #fff; }
.card-divider { margin: 10px 0 25px; border-top: 1px dashed #F2F2F2; }
.product-section { padding: 0 20px; }
.row-titles { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; color: #333; font-size: 15px; }
.receiver-tag { font-weight: bold; }
.product-item { display: flex; align-items: flex-start; margin-bottom: 25px; }
.multi-item { margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px dashed #f5f5f5; }
.p-img { width: 78px; height: 78px; margin-right: 12px; border-radius: 6px; object-fit: cover; }
.small-img { width: 60px; height: 60px; }
.p-box { flex: 1; }
.p-title { color: #111; font-size: 14px; font-weight: 600; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.one-line { -webkit-line-clamp: 1; }
.item-price { margin-top: 6px; color: #000; font-weight: bold; }
.multi-summary { margin-bottom: 15px; text-align: right; color: #666; font-size: 13px; }
.share-actions { display: flex; gap: 12px; padding: 0 20px; }
.share-action { flex: 1; height: 44px; border: 1px solid #00C2C9; border-radius: 4px; background: #fff; color: #00C2C9; font-weight: bold; }
.poster-action { background: #00C2C9; color: #fff; }
.serrated-edge { height: 20px; margin: 0 12px; background: radial-gradient(circle at 10px -2px, transparent 10px, #fff 11px) repeat-x; background-size: 20px 20px; }
.poster-modal, .share-preparing-modal { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; }
.poster-modal { z-index: 9999; flex-direction: column; background: rgba(0,0,0,.85); }
.poster-modal-close { position: absolute; top: 20px; right: 20px; color: #fff; font-size: 35px; }
.poster-image { width: 80%; max-width: 330px; border-radius: 12px; }
.poster-tip { margin-top: 15px; color: #fff; font-size: 14px; }
.share-preparing-modal { z-index: 10001; background: rgba(0,0,0,.45); }
.share-preparing-card { width: min(100%, 280px); padding: 28px 24px; border-radius: 16px; background: #fff; text-align: center; }
.share-preparing-spinner { width: 40px; height: 40px; margin: 0 auto 16px; border: 3px solid #d2f4f5; border-top-color: #00C2C9; border-radius: 50%; animation: sharePreparingSpin .9s linear infinite; }
.share-preparing-title { margin-bottom: 8px; font-size: 16px; font-weight: 600; }
.share-preparing-text { color: #8a8f99; font-size: 13px; line-height: 1.6; }
.poster-canvas { position: fixed; top: 0; left: -9999px; width: 375px; background: #F8F8F8; }
.poster-card { margin-top: -9px; }
.poster-product { margin-bottom: 10px; }
.poster-img { width: 50px; height: 50px; }
.poster-qr { display: block; width: max-content; margin: 0 auto; padding: 5px; background: #fff; }
.poster-qr-tip { margin: 8px 0 20px; text-align: center; color: #999; font-size: 12px; }
@keyframes sharePreparingSpin { from { transform: rotate(0); } to { transform: rotate(360deg); } }
</style>
