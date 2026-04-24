<template>
  <div class="cashier-ctrip">
    <div class="ctrip-header">
      <div class="header-content">
        <img :src="getPublicImageUrl('/xiec.png')" class="logo-img" alt="Ctrip" crossorigin="anonymous">
        <div class="header-text-col">
          <div class="route-title">{{ productName }}</div>
          <div class="pay-msg">{{ slogan }}</div>
        </div>
      </div>
    </div>

    <div class="main-card">
      <div v-if="!isPaid" class="timer-bar">
        剩余支付时间：<span id="timer_display">{{ minutes }}:{{ seconds }}</span>，请尽快完成支付。
      </div>
      <div v-else class="timer-bar paid-bar">
        该订单已完成支付
      </div>

      <div class="pay-area">
        <div class="pay-label">待付金额</div>
        <div class="pay-price">{{ order.money }}</div>
      </div>

      <div class="info-box">
        <div class="info-title">代付说明</div>
        <div>1. 付款前务必和好友再次确认，避免诈骗行为。</div>
        <div>2. 如果发生退款，钱将退还到付款人的微信账户里。</div>
      </div>

      <div v-if="!isPaid && !isExpired" class="share-buttons">
        <button
          class="btn-ctrip btn-outline-blue"
          @click="handleGeneratePoster"
          :disabled="isGeneratingPoster"
        >
          {{ isGeneratingPoster ? '海报生成中...' : '生成海报分享' }}
        </button>
        <button
          class="btn-ctrip btn-outline-orange"
          @click="handleCardShare"
          :disabled="isBindingWechatShare"
        >
          {{ isBindingWechatShare ? '正在准备分享...' : '微信卡片分享' }}
        </button>
      </div>

      <img v-if="isPaid" :src="getPublicImageUrl('/yzf.png')" class="stamp-img" alt="已支付">
      <img v-else-if="isExpired" :src="getPublicImageUrl('/guiqi.png')" class="stamp-img stamp-expired" alt="已过期">
    </div>

    <div class="bottom-info-card">
      订单信息：<span class="order-no">{{ order.outTradeNo }}</span>
    </div>

    <div v-if="showPosterModal" class="poster-modal" @click="closePosterModal">
      <div class="poster-modal-close">×</div>
      <img v-if="posterImageUrl" :src="posterImageUrl" class="poster-image" alt="海报">
      <div class="poster-tip">长按图片保存到相册</div>
    </div>

    <div v-if="showSharePreparingModal" class="share-preparing-modal">
      <div class="share-preparing-card">
        <div class="share-preparing-spinner"></div>
        <div class="share-preparing-title">正在准备微信分享功能</div>
        <div class="share-preparing-text">请稍候，准备完成后再点击右上角三个点进行分享</div>
      </div>
    </div>

    <div ref="posterCanvas" class="poster-canvas">
      <div class="ctrip-header">
        <div class="header-content">
          <img :src="getPublicImageUrl('/xiec.png')" class="logo-img" alt="Ctrip" crossorigin="anonymous">
          <div class="header-text-col">
            <div class="route-title">{{ productName }}</div>
            <div class="pay-msg">{{ slogan }}</div>
          </div>
        </div>
      </div>
      <div class="main-card">
        <div class="pay-area poster-pay-area">
          <div class="pay-label">待付金额</div>
          <div class="pay-price">{{ order.money }}</div>
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
import html2canvas from 'html2canvas'
import QRCode from 'qrcodejs2'

export default {
  name: 'CashierCtrip',
  props: {
    order: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      remainingSeconds: 0,
      timer: null,
      showPosterModal: false,
      posterImageUrl: '',
      isGeneratingPoster: false,
      isBindingWechatShare: false,
      showSharePreparingModal: false,
      shareReady: false,
      shareInitPromise: null,
      wechatConfigReady: false,
      wechatConfigUrl: '',
      shareCardConfig: null
    }
  },
  computed: {
    isPaid() {
      return String(this.order.status) === '1'
    },
    isExpired() {
      return this.remainingSeconds <= 0 && !this.isPaid
    },
    minutes() {
      const m = Math.floor(this.remainingSeconds / 60)
      return m < 10 ? '0' + m : m
    },
    seconds() {
      const s = this.remainingSeconds % 60
      return s < 10 ? '0' + s : s
    },
    orderItems() {
      try {
        if (typeof this.order.items === 'string') {
          return JSON.parse(this.order.items)
        }
        return this.order.items || []
      } catch (error) {
        return []
      }
    },
    productName() {
      return this.order.orderName || '携程旅行订单'
    },
    slogan() {
      return '我在携程下了一个订单，是时候该你仗义疏财啦，快帮我付款吧'
    }
  },
  watch: {
    'order.outTradeNo': {
      handler(newVal, oldVal) {
        if (newVal && newVal !== oldVal) {
          this.shareReady = false
          this.wechatConfigReady = false
          this.wechatConfigUrl = ''
          setTimeout(() => {
            this.prepareWechatShare()
          }, 500)
        }
      },
      immediate: false
    }
  },
  mounted() {
    this.applyDocumentTitle()
    this.fetchShareCardConfig()
    this.calculateRemainingTime()
    this.startTimer()

    if (this.order.outTradeNo) {
      this.prepareWechatShare()
    }
  },
  beforeDestroy() {
    this.stopTimer()
  },
  methods: {
    async fetchShareCardConfig() {
      if (!this.order.tpl) {
        return
      }

      try {
        const { getShareCardConfigByTpl } = await import('@/api/shareCardConfig')
        const res = await getShareCardConfigByTpl(this.order.tpl)

        if (res.code === 200 && res.data) {
          this.shareCardConfig = res.data
          this.applyDocumentTitle()
        }
      } catch (error) {
        console.error('获取分享卡片配置失败:', error)
      }
    },

    applyDocumentTitle() {
      document.title = this.shareCardConfig?.cardName || '携程旅行代付'
    },

    async prepareWechatShare() {
      const { isWechat } = await import('@/utils/device')
      if (!isWechat() || !this.order.outTradeNo) {
        return
      }

      try {
        this.showSharePreparingModal = true
        await this.fetchShareCardConfig()
        await this.ensureWechatShareReady()
      } catch (error) {
        console.error('微信分享初始化失败:', error)
        this.$message.error(error.message || '微信分享初始化失败')
      } finally {
        this.showSharePreparingModal = false
      }
    },

    getShareOrigin() {
      const customOrigin = (process.env.VUE_APP_SHARE_ORIGIN || '').trim()
      if (customOrigin) {
        return customOrigin.replace(/\/$/, '')
      }
      return window.location.origin
    },

    getPublicImageUrl(path) {
      const publicPath = process.env.BASE_URL || '/'
      return publicPath + path.replace(/^\//, '')
    },

    getShareLandingUrl() {
      const outTradeNo = encodeURIComponent(this.order.outTradeNo || '')
      return `${this.getShareOrigin()}/share/cashier?outTradeNo=${outTradeNo}&from=share`
    },

    calculateRemainingTime() {
      if (this.isPaid || !this.order.expireTime) {
        this.remainingSeconds = 0
        return
      }

      const expireTime = new Date(this.order.expireTime).getTime()
      const now = Date.now()
      const diff = Math.floor((expireTime - now) / 1000)
      this.remainingSeconds = diff > 0 ? diff : 0
    },

    startTimer() {
      if (this.isPaid || this.remainingSeconds <= 0) {
        return
      }

      this.timer = setInterval(() => {
        if (this.remainingSeconds > 0) {
          this.remainingSeconds--
        } else {
          this.stopTimer()
        }
      }, 1000)
    },

    stopTimer() {
      if (this.timer) {
        clearInterval(this.timer)
        this.timer = null
      }
    },

    getImageUrl(image) {
      if (!image) {
        return ''
      }
      if (image.startsWith('http://') || image.startsWith('https://')) {
        return image
      }
      return `/api${image}`
    },

    async handleGeneratePoster() {
      if (this.isGeneratingPoster) {
        return
      }

      if (!this.order.outTradeNo) {
        this.$message.error('订单号不存在，无法生成海报')
        return
      }

      if (this.isPaid) {
        this.$message.warning('订单已支付，无需生成海报')
        return
      }

      if (this.isExpired) {
        this.$message.warning('订单已过期，无法生成海报')
        return
      }

      try {
        this.isGeneratingPoster = true
        await this.$nextTick()
        await new Promise(resolve => setTimeout(resolve, 100))

        const posterCanvas = this.$refs.posterCanvas
        const qrContainer = this.$refs.posterQrContainer
        if (!posterCanvas || !qrContainer) {
          throw new Error('海报容器不存在')
        }

        qrContainer.innerHTML = ''

        new QRCode(qrContainer, {
          text: this.getPosterQrUrl(),
          width: 140,
          height: 140,
          colorLight: '#ffffff',
          correctLevel: QRCode.CorrectLevel.H
        })

        await new Promise(resolve => setTimeout(resolve, 800))

        const canvas = await html2canvas(posterCanvas, {
          useCORS: true,
          scale: 2,
          allowTaint: false,
          backgroundColor: '#f4f6f8'
        })

        this.posterImageUrl = canvas.toDataURL('image/png', 1.0)
        this.showPosterModal = true
      } catch (error) {
        console.error('生成海报失败:', error)
        this.$message.error('生成海报失败，请稍后重试')
      } finally {
        this.isGeneratingPoster = false
      }
    },

    closePosterModal() {
      this.showPosterModal = false
      this.posterImageUrl = ''
    },

    normalizeShareUrl(url) {
      if (!url) {
        return ''
      }

      const trimmedUrl = String(url).trim()
      try {
        return encodeURI(trimmedUrl)
      } catch (error) {
        return trimmedUrl
      }
    },

    createWechatShareData() {
      return {
        title: this.shareCardConfig?.shareTitle || '携程旅行代付',
        desc: this.shareCardConfig?.shareDesc || `我在携程下了一个订单，快帮我付款吧 金额：${this.order.money}`,
        link: this.normalizeShareUrl(this.getShareLandingUrl()),
        imgUrl: this.normalizeShareUrl(this.getShareImage())
      }
    },

    getPosterQrUrl() {
      return this.createWechatShareData().link
    },

    async syncWechatShareData() {
      const { setShareToFriend } = await import('@/utils/wechat')
      return setShareToFriend(this.createWechatShareData())
    },

    async ensureWechatShareReady() {
      if (this.shareReady) {
        return
      }

      if (this.shareInitPromise) {
        return this.shareInitPromise
      }

      this.isBindingWechatShare = true

      this.shareInitPromise = this.initWechatShare()
        .then(() => {
          this.shareReady = true
        })
        .catch((error) => {
          this.shareReady = false
          this.wechatConfigReady = false
          this.wechatConfigUrl = ''
          throw error
        })
        .finally(() => {
          this.isBindingWechatShare = false
          this.shareInitPromise = null
        })

      return this.shareInitPromise
    },

    async configureWechatShareConfig(signUrl, allowRealAuthRetry = true) {
      const { getJsapiSignature } = await import('@/api/wechat')
      const { initWechatConfig } = await import('@/utils/wechat')

      const normalizedSignUrl = this.normalizeShareUrl(signUrl)
      const res = await getJsapiSignature(normalizedSignUrl)
      if (res.code !== 200 || !res.data) {
        throw new Error(res.msg || '获取微信签名失败')
      }

      try {
        await initWechatConfig(res.data)
        this.wechatConfigReady = true
        this.wechatConfigUrl = normalizedSignUrl
        return normalizedSignUrl
      } catch (error) {
        const realAuthUrl = Array.isArray(error && error.realAuthUrl) ? error.realAuthUrl[0] : ''
        const normalizedRealAuthUrl = this.normalizeShareUrl(realAuthUrl)

        if (
          allowRealAuthRetry &&
          normalizedRealAuthUrl &&
          normalizedRealAuthUrl !== normalizedSignUrl
        ) {
          return this.configureWechatShareConfig(normalizedRealAuthUrl, false)
        }

        throw error
      }
    },

    async initWechatShare() {
      await new Promise(resolve => setTimeout(resolve, 300))

      const { isWechat } = await import('@/utils/device')
      if (!isWechat() || !this.order.outTradeNo) {
        return
      }

      try {
        const { setShareToFriend } = await import('@/utils/wechat')
        const signUrl = this.normalizeShareUrl(window.location.href.split('#')[0])

        if (this.wechatConfigReady && this.wechatConfigUrl === signUrl) {
          await this.syncWechatShareData()
          return
        }

        await this.configureWechatShareConfig(signUrl)
        await new Promise(resolve => setTimeout(resolve, 1000))
        await setShareToFriend(this.createWechatShareData())
      } catch (error) {
        this.wechatConfigReady = false
        this.wechatConfigUrl = ''
        throw error
      }
    },

    getShareImage() {
      if (this.shareCardConfig && this.shareCardConfig.useProductImage === '1') {
        if (this.orderItems.length > 0 && this.orderItems[0].image) {
          const image = this.orderItems[0].image
          if (image.startsWith('http://') || image.startsWith('https://')) {
            return image
          }
          return `${window.location.origin}${this.getImageUrl(image)}`
        }
      } else if (this.shareCardConfig && this.shareCardConfig.shareImage) {
        const shareImage = this.shareCardConfig.shareImage
        if (shareImage.startsWith('http://') || shareImage.startsWith('https://')) {
          return shareImage
        }
        return `${window.location.origin}/api${shareImage}`
      }

      if (this.orderItems.length > 0 && this.orderItems[0].image) {
        const image = this.orderItems[0].image
        if (image.startsWith('http://') || image.startsWith('https://')) {
          return image
        }
        return `${window.location.origin}${this.getImageUrl(image)}`
      }

      return ''
    },

    async handleCardShare() {
      const { isWechat } = await import('@/utils/device')

      if (!isWechat()) {
        this.$message.warning('请在微信中打开此页面进行分享')
        return
      }

      if (!this.order.outTradeNo) {
        this.$message.error('订单号不存在，无法分享')
        return
      }

      if (!this.shareReady) {
        this.$message.warning('微信分享功能仍在准备中，请稍后再试')
        return
      }

      await this.$alert(
        '微信分享已就绪，请点击右上角 “...” 选择“发送给朋友”。',
        '分享已就绪',
        {
          confirmButtonText: '知道了',
          center: true
        }
      )
    }
  }
}
</script>

<style scoped>
* { box-sizing: border-box; }

.cashier-ctrip {
  min-height: 100vh;
  background-color: #F4F6F8;
  padding-bottom: 20px;
}

.ctrip-header {
  margin: 12px 12px 0 12px;
  border-radius: 12px 12px 0 0;
  background: linear-gradient(180deg, #499BF7 0%, #2986F6 100%);
  padding: 25px 20px 80px 20px;
  color: #fff;
  position: relative;
}

.header-content {
  display: flex;
  align-items: center;
}

.logo-img {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  margin-right: 12px;
  background: #fff;
  padding: 8px;
  object-fit: contain;
  flex-shrink: 0;
}

.header-text-col {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.route-title {
  font-size: 15px;
  font-weight: bold;
  color: #fff;
  margin-bottom: 2px;
  line-height: 1.3;
}

.pay-msg {
  font-size: 13px;
  color: #fff;
  line-height: 1.4;
  font-weight: normal;
  opacity: 0.95;
}

.main-card {
  background: #fff;
  margin: 0 12px;
  border-radius: 12px;
  position: relative;
  top: -60px;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
  overflow: hidden;
  padding-bottom: 20px;
  z-index: 10;
}

.timer-bar {
  background: #FFF8F2;
  color: #FF7D00;
  font-size: 13px;
  padding: 14px 15px;
  text-align: center;
  font-weight: 500;
}

.timer-bar.paid-bar {
  color: #28a745;
  background: #e8f5e9;
}

.pay-area {
  padding: 35px 20px 10px 20px;
  text-align: center;
}

.poster-pay-area {
  padding-bottom: 20px;
}

.pay-label {
  color: #666;
  font-size: 15px;
  margin-bottom: 10px;
}

.pay-price {
  font-size: 42px;
  color: #333;
  font-weight: bold;
  font-family: Arial, sans-serif;
  margin-bottom: 35px;
  letter-spacing: -1px;
}

.pay-price::before {
  content: '￥';
  font-size: 26px;
  margin-right: 4px;
  font-weight: normal;
}

.btn-ctrip {
  display: block;
  width: 100%;
  background: linear-gradient(90deg, #FFA500 0%, #FF7700 100%);
  color: #fff;
  font-size: 18px;
  font-weight: bold;
  text-align: center;
  padding: 14px 0;
  border-radius: 6px;
  text-decoration: none;
  border: none;
  box-shadow: 0 6px 15px rgba(255, 119, 0, 0.3);
  margin-bottom: 15px;
  cursor: pointer;
}

.btn-ctrip:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-outline-blue {
  background: #fff;
  color: #0086F6;
  border: 1px solid #0086F6;
  font-size: 16px;
  box-shadow: none;
}

.btn-outline-orange {
  background: #fff;
  color: #FF7700;
  border: 1px solid #FF7700;
  font-size: 16px;
  box-shadow: none;
  margin-bottom: 0;
}

.info-box {
  background: #F7F8FA;
  border-radius: 8px;
  margin: 0 20px 20px 20px;
  padding: 18px;
  color: #888;
  font-size: 12px;
  line-height: 1.8;
}

.info-title {
  color: #333;
  font-weight: bold;
  font-size: 14px;
  margin-bottom: 6px;
}

.share-buttons {
  padding: 0 20px;
}

.bottom-info-card {
  background: #fff;
  margin: -45px 12px 20px 12px;
  border-radius: 12px;
  padding: 15px 20px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
  position: relative;
  z-index: 5;
  color: #666;
  font-size: 13px;
}

.order-no {
  font-family: monospace;
}

.stamp-img {
  position: absolute;
  right: 10px;
  top: 50px;
  width: 70px;
  opacity: 0.95;
  transform: rotate(-15deg);
  z-index: 20;
  pointer-events: none;
}

.stamp-expired {
  filter: grayscale(100%);
  opacity: 0.6;
}

.poster-modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.9);
  z-index: 9999;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
}

.poster-modal-close {
  position: absolute;
  top: 20px;
  right: 20px;
  color: #fff;
  font-size: 35px;
  cursor: pointer;
  z-index: 10000;
}

.poster-image {
  width: 85%;
  max-width: 320px;
  border-radius: 12px;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
}

.poster-tip {
  color: #fff;
  margin-top: 20px;
  font-size: 14px;
}

.share-preparing-modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
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
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.18);
}

.share-preparing-spinner {
  width: 40px;
  height: 40px;
  margin: 0 auto 16px;
  border: 3px solid #d6e9ff;
  border-top-color: #0086F6;
  border-radius: 50%;
  animation: sharePreparingSpin 0.9s linear infinite;
}

.share-preparing-title {
  margin-bottom: 8px;
  font-size: 16px;
  font-weight: 600;
  color: #222;
}

.share-preparing-text {
  font-size: 13px;
  line-height: 1.6;
  color: #8a8f99;
}

.poster-canvas {
  position: absolute;
  top: 0;
  left: -9999px;
  width: 375px;
  background: #f4f6f8;
  padding-bottom: 30px;
  z-index: 1;
}

.poster-qr-wrap {
  text-align: center;
  padding: 20px;
}

.poster-qr {
  display: inline-block;
}

.poster-qr-tip {
  font-size: 12px;
  color: #999;
  margin-top: 10px;
}

@keyframes sharePreparingSpin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
