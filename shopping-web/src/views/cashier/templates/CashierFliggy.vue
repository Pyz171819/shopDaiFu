<template>
  <div class="cashier-fliggy">


    <div class="container">
      <div class="user-info-row">
        <img :src="userAvatar" class="avatar-img" alt="avatar">
        <div class="user-content">
          <div class="nick-line">{{ userNick }}<span class="nick-suffix">(**萌)</span></div>
          <div class="chat-bubble">我有一张订单需要支付，请帮我代付吧。非常感谢你~</div>
        </div>
      </div>

      <div class="orange-card">
        <div class="card-header">
          <span>帮我付订单信息</span>
          <span>{{ isExpired ? '已过期' : '待支付' }}</span>
        </div>
        <div class="price-row"><span class="price-symbol">￥</span><span class="price-val">{{ order.money }}</span></div>
        <div class="card-footer">实际金额以付款人确认付款时为准</div>
        <div class="card-heart">❤</div>
      </div>

      <div class="product-box-colored">
        <img :src="productImage" class="product-img" alt="product">
        <div class="product-name">{{ productName }}</div>
      </div>

      <div v-if="!isPaid && !isExpired" class="manager-tools">
        <button class="tool-btn poster-btn" @click="handleGeneratePoster" :disabled="isGeneratingPoster">生成海报</button>
        <button class="tool-btn share-btn" @click="handleCardShare" :disabled="isBindingWechatShare">发送给好友</button>
      </div>

      <div class="instr-section">
        <div class="instr-title">帮我付说明：</div>
        <div class="instr-item">1. 请在自愿和确认好友身份后使用代付功能。</div>
        <div class="instr-item">2. 付款前请务必确认认付方身份，以免造成诈骗行为。</div>
        <div class="instr-item instr-orange">3. 如果交易发生退款，已支付金额将原路退回付款账户。</div>
      </div>

      <div class="provider-footer">本服务由飞猪旅行提供技术支持</div>
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
      <div class="fliggy-header"><div class="header-title">飞猪代付</div></div>
      <div class="container poster-container">
        <div class="user-info-row">
          <img :src="userAvatar" class="avatar-img" alt="avatar" crossorigin="anonymous">
          <div class="user-content">
            <div class="nick-line">{{ userNick }}<span class="nick-suffix">(**萌)</span></div>
            <div class="chat-bubble">我有一张订单需要支付，请帮我代付吧。</div>
          </div>
        </div>
        <div class="orange-card">
          <div class="card-header">帮我付订单信息</div>
          <div class="price-row"><span class="price-symbol">￥</span><span class="price-val">{{ order.money }}</span></div>
        </div>
        <div class="product-box-colored">
          <img :src="productImage" class="product-img" alt="product" crossorigin="anonymous">
          <div class="product-name">{{ productName }}</div>
        </div>
        <div class="poster-qr-wrap">
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
  name: 'CashierFliggy',
  mixins: [shareTemplateMixin],
  computed: {
    pageTitle() {
      return '飞猪代付'
    },
    userNick() {
      return this.userInfo?.nickname || '旅行用户'
    },
    userAvatar() {
      if (this.userInfo?.avatar) return this.getImageUrl(this.userInfo.avatar)
      return 'https://img.yzcdn.cn/vant/cat.jpeg'
    }
  },
  methods: {
    getDefaultShareTitle() {
      return '飞猪旅行特惠'
    },
    getDefaultShareDesc() {
      return '发现了一个超棒的旅行套餐，帮我付一下，一起去玩吧！'
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
      return { useCORS: true, scale: 2, allowTaint: false, backgroundColor: '#F7F7F7' }
    }
  }
}
</script>

<style scoped>
* { box-sizing: border-box; }
.cashier-fliggy { min-height: 100vh; padding-bottom: 32px; background: #F7F7F7; color: #222; }
.fliggy-header { position: relative; padding: 16px 20px; border-bottom: 1px solid #eee; background: #fff; }
.header-title { color: #000; font-size: 17px; font-weight: 600; }
.header-domain { display: block; margin-top: 2px; color: #999; font-size: 11px; }
.container { padding: 24px 18px; }
.user-info-row { display: flex; align-items: flex-start; margin-bottom: 20px; }
.avatar-img { width: 48px; height: 48px; margin-right: 12px; border-radius: 50%; object-fit: cover; }
.user-content { flex: 1; }
.nick-line { margin-bottom: 8px; color: #222; font-size: 16px; font-weight: 700; }
.nick-suffix { margin-left: 4px; color: #999; font-size: 12px; font-weight: 400; }
.chat-bubble { display: inline-block; padding: 10px 12px; border-radius: 10px; background: #fff; color: #666; font-size: 13px; line-height: 1.5; box-shadow: 0 2px 8px rgba(0,0,0,.04); }
.orange-card { position: relative; overflow: hidden; margin-bottom: 15px; padding: 20px; border-radius: 16px; background: linear-gradient(135deg,#ff9c2b,#ff681f); color: #fff; }
.card-header { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 13px; opacity: .95; }
.price-row { display: flex; align-items: baseline; margin-bottom: 25px; }
.price-symbol { margin-right: 2px; font-size: 22px; }
.price-val { font-family: Arial, sans-serif; font-size: 38px; font-weight: bold; }
.card-footer { padding-top: 12px; border-top: .5px solid rgba(255,255,255,.3); font-size: 12px; opacity: .85; }
.card-heart { position: absolute; right: -10px; bottom: -20px; font-size: 110px; opacity: .12; transform: rotate(15deg); }
.product-box-colored { display: flex; align-items: center; margin-bottom: 20px; padding: 14px; border: .5px solid #FFF2CC; border-radius: 12px; background: #fffaf0; }
.product-img { width: 62px; height: 62px; margin-right: 12px; border-radius: 8px; object-fit: cover; }
.product-name { flex: 1; color: #333; font-size: 14px; font-weight: 500; line-height: 1.4; }
.manager-tools { display: flex; gap: 12px; margin-bottom: 24px; }
.tool-btn { flex: 1; height: 42px; border-radius: 21px; border: 1px solid #ff7a1a; background: #fff; color: #ff7a1a; font-weight: 700; }
.poster-btn { background: #ff7a1a; color: #fff; }
.instr-section { color: #666; font-size: 12px; line-height: 1.7; }
.instr-title { margin-bottom: 10px; color: #333; font-size: 14px; font-weight: bold; }
.instr-orange { color: #ff7a1a; }
.provider-footer { margin-top: 50px; color: #bbb; text-align: center; font-size: 11px; }
.poster-modal, .share-preparing-modal { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; }
.poster-modal { z-index: 9999; flex-direction: column; background: rgba(0,0,0,.85); }
.poster-modal-close { position: absolute; top: 20px; right: 20px; color: #fff; font-size: 35px; }
.poster-image { width: 80%; max-width: 330px; border-radius: 12px; }
.poster-tip { margin-top: 15px; color: #fff; font-size: 14px; }
.share-preparing-modal { z-index: 10001; background: rgba(0,0,0,.45); }
.share-preparing-card { width: min(100%, 280px); padding: 28px 24px; border-radius: 16px; background: #fff; text-align: center; }
.share-preparing-spinner { width: 40px; height: 40px; margin: 0 auto 16px; border: 3px solid #ffe4c7; border-top-color: #ff7a1a; border-radius: 50%; animation: sharePreparingSpin .9s linear infinite; }
.share-preparing-title { margin-bottom: 8px; font-size: 16px; font-weight: 600; }
.share-preparing-text { color: #8a8f99; font-size: 13px; line-height: 1.6; }
.poster-canvas { position: fixed; top: 0; left: -9999px; width: 375px; padding-bottom: 30px; background: #F7F7F7; }
.poster-container { padding-top: 20px; }
.poster-qr-wrap { text-align: center; }
.poster-qr {
  display: flex;
  width: 150px;
  min-height: 150px;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
  padding: 5px;
  background: #fff;
}
.poster-qr-tip { margin-top: 8px; color: #999; font-size: 12px; }
@keyframes sharePreparingSpin { from { transform: rotate(0); } to { transform: rotate(360deg); } }
</style>
