<template>
  <div class="cashier-pinduoduo">
    <div class="pdd-header">
      <img :src="userAvatar" class="avatar-img" alt="avatar" crossorigin="anonymous">
      <div class="user-col">
        <div class="nick-name">{{ userNick }}</div>
        <div class="bubble-msg">帮我付一下这件商品吧，谢谢啦</div>
      </div>
    </div>

    <div class="main-card">
      <div class="pay-label">代付金额</div>

      <div class="price-wrap">
        <span class="symbol">¥</span>
        <span class="amount">{{ order.money }}</span>
      </div>

      <div class="note-text">如订单发生退款，已支付金额将原路退回给代付人</div>

      <div class="prod-section">
        <div v-if="isMultiItem">
          <div
            v-for="(item, index) in orderItems"
            :key="index"
            class="prod-item-row"
          >
            <img :src="getImageUrl(item.image)" class="prod-img" alt="product">
            <div class="prod-info">
              <div class="prod-title">{{ item.name }}</div>
              <div class="prod-price-row">
                <span class="price-small">¥{{ item.price }}</span>
                <span>x{{ item.quantity || 1 }}</span>
              </div>
            </div>
          </div>
          <div class="total-summary">
            共 {{ orderItems.length }} 件商品，合计
            <span class="total-price">¥{{ order.money }}</span>
          </div>
        </div>

        <div v-else class="prod-item-row">
          <img :src="productImage" class="prod-img" alt="product">
          <div class="prod-info">
            <div class="prod-title">{{ productName }}</div>
            <div class="prod-spec">默认规格</div>
            <div class="prod-price-row">
              <span class="price-small">¥{{ order.money }}</span>
              <span>x1</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="!isPaid && !isExpired" class="manager-tools">
      <button
        class="btn-outline btn-poster"
        @click="handleGeneratePoster"
        :disabled="isGeneratingPoster"
      >
        {{ isGeneratingPoster ? '生成中...' : '生成海报' }}
      </button>
      <button
        class="btn-outline"
        @click="handleCardShare"
        :disabled="isBindingWechatShare"
      >
        {{ isBindingWechatShare ? '准备中...' : '发送给好友' }}
      </button>
    </div>

    <div v-if="showPosterModal" class="poster-modal" @click="closePosterModal">
      <div class="poster-modal-close">×</div>
      <img v-if="posterImageUrl" :src="posterImageUrl" class="poster-image" alt="poster">
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
      <div class="pdd-header">
        <img :src="userAvatar" class="avatar-img" alt="avatar" crossorigin="anonymous">
        <div class="user-col">
          <div class="nick-name">{{ userNick }}</div>
          <div class="bubble-msg">帮我付一下这件商品吧，谢谢啦</div>
        </div>
      </div>
      <div class="main-card">
        <div class="pay-label">代付金额</div>
        <div class="price-wrap">
          <span class="symbol">¥</span>
          <span class="amount">{{ order.money }}</span>
        </div>
        <div class="poster-qr-wrap">
          <div ref="posterQrContainer" class="poster-qr"></div>
          <div class="poster-qr-tip">长按识别二维码代付</div>
        </div>
        <div class="prod-section">
          <div v-if="isMultiItem">
            <div
              v-for="(item, index) in orderItems.slice(0, 3)"
              :key="index"
              class="prod-item-row"
            >
              <img :src="getImageUrl(item.image)" class="prod-img" alt="product" crossorigin="anonymous">
              <div class="prod-info">
                <div class="prod-title">{{ item.name }}</div>
                <div class="prod-price-row">
                  <span class="price-small">¥{{ item.price }}</span>
                  <span>x{{ item.quantity || 1 }}</span>
                </div>
              </div>
            </div>
            <div v-if="orderItems.length > 3" class="poster-more-tip">
              ... 等 {{ orderItems.length }} 件商品
            </div>
          </div>
          <div v-else class="prod-item-row">
            <img :src="productImage" class="prod-img" alt="product" crossorigin="anonymous">
            <div class="prod-info">
              <div class="prod-title">{{ productName }}</div>
              <div class="prod-spec">默认规格</div>
              <div class="prod-price-row">
                <span class="price-small">¥{{ order.money }}</span>
                <span>x1</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import html2canvas from 'html2canvas'
import QRCode from 'qrcodejs2'

export default {
  name: 'CashierPinduoduo',
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
      userInfo: null,
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
    isMultiItem() {
      return this.orderItems.length > 1
    },
    productName() {
      return this.order.orderName || '拼多多商品'
    },
    productImage() {
      if (this.orderItems.length > 0) {
        return this.getImageUrl(this.orderItems[0].image)
      }
      return ''
    },
    userNick() {
      if (this.userInfo && this.userInfo.nickname) {
        return this.userInfo.nickname
      }
      return '拼多多用户'
    },
    userAvatar() {
      if (this.userInfo && this.userInfo.avatar) {
        return this.getImageUrl(this.userInfo.avatar)
      }
      return 'https://img.yzcdn.cn/vant/cat.jpeg'
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
    document.title = '拼多多代付'
    this.calculateRemainingTime()
    this.startTimer()
    this.fetchUserInfo()

    if (this.order.outTradeNo) {
      this.prepareWechatShare()
    }
  },
  beforeDestroy() {
    this.stopTimer()
  },
  methods: {
    async fetchUserInfo() {
      try {
        const { getUserSimpleInfo } = await import('@/api/auth')
        const res = await getUserSimpleInfo()
        const profile = res.user || res.data || res

        if (profile && (profile.userId || profile.id)) {
          this.userInfo = {
            nickname: profile.nickName || profile.nickname || profile.userName || profile.username || '拼多多用户',
            avatar: profile.avatar || ''
          }
        }
      } catch (error) {
        console.error('获取用户信息失败:', error)
        this.userInfo = {
          nickname: '拼多多用户',
          avatar: ''
        }
      }
    },

    async fetchShareCardConfig() {
      try {
        const { getShareCardConfigByTpl } = await import('@/api/shareCardConfig')
        const res = await getShareCardConfigByTpl(this.order.tpl)

        if (res.code === 200 && res.data) {
          this.shareCardConfig = res.data
        }
      } catch (error) {
        console.error('获取分享卡片配置失败:', error)
      }
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
          colorDark: '#E02E24',
          colorLight: '#ffffff',
          correctLevel: QRCode.CorrectLevel.H
        })

        await new Promise(resolve => setTimeout(resolve, 800))

        const canvas = await html2canvas(posterCanvas, {
          useCORS: true,
          scale: 2,
          allowTaint: false,
          backgroundColor: '#f4f4f4'
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
        title: this.shareCardConfig?.shareTitle || '希望你帮他付款',
        desc: this.shareCardConfig?.shareDesc || `我在拼多多上买到了很赞的东西，希望你帮我付款哦~ 金额：${this.order.money}`,
        link: this.normalizeShareUrl(this.getShareLandingUrl()),
        imgUrl: this.normalizeShareUrl(this.getShareImage())
      }
    },

    getPosterQrUrl() {
      return this.createWechatShareData().link
    },

    async syncWechatShareData() {
      const { setShareToFriend } = await import('@/utils/wechat')
      const shareData = this.createWechatShareData()
      const normalizedShareData = await setShareToFriend(shareData)
      return normalizedShareData
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

        const shareData = this.createWechatShareData()
        await setShareToFriend(shareData)
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

.cashier-pinduoduo {
  min-height: 100vh;
  background-color: #F4F4F4;
  padding-bottom: 20px;
}

.pdd-header {
  background-color: #F3554F;
  padding: 20px 20px 60px;
  display: flex;
  align-items: flex-start;
}

.avatar-img {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  margin-right: 12px;
  object-fit: cover;
  border: 1px solid rgba(255, 255, 255, 0.2);
  flex-shrink: 0;
}

.user-col {
  display: flex;
  flex-direction: column;
  padding-top: 2px;
  flex: 1;
}

.nick-name {
  color: #fff;
  font-size: 16px;
  font-weight: 500;
  margin-bottom: 8px;
}

.bubble-msg {
  background-color: rgba(255, 255, 255, 0.2);
  color: #fff;
  font-size: 13px;
  padding: 6px 12px;
  border-radius: 4px;
  position: relative;
  line-height: 1.4;
  max-width: 210px;
  word-wrap: break-word;
}

.bubble-msg::before {
  content: '';
  position: absolute;
  left: -6px;
  top: 50%;
  margin-top: -6px;
  border-width: 6px 6px 6px 0;
  border-style: solid;
  border-color: transparent rgba(255, 255, 255, 0.2) transparent transparent;
}

.main-card {
  background: #fff;
  margin: -40px 12px 20px;
  border-radius: 8px;
  padding: 30px 20px 0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
  text-align: center;
}

.pay-label {
  font-size: 15px;
  color: #333;
  margin-bottom: 15px;
  font-weight: 500;
}

.price-wrap {
  color: #000;
  font-weight: bold;
  font-family: Arial, sans-serif;
  margin-bottom: 25px;
  display: flex;
  align-items: baseline;
  justify-content: center;
}

.symbol {
  font-size: 24px;
  margin-right: 4px;
}

.amount {
  font-size: 42px;
  letter-spacing: -1px;
}

.note-text {
  color: #9C9C9C;
  font-size: 12px;
  margin-bottom: 25px;
}

.prod-section {
  border-top: 1px solid #F2F2F2;
  padding: 20px 0;
  text-align: left;
}

.prod-item-row {
  display: flex;
  align-items: center;
  margin-bottom: 15px;
}

.prod-item-row:last-child {
  margin-bottom: 0;
}

.prod-img {
  width: 60px;
  height: 60px;
  border-radius: 4px;
  object-fit: cover;
  margin-right: 12px;
  flex-shrink: 0;
  background: #f8f8f8;
}

.prod-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  overflow: hidden;
}

.prod-title {
  font-size: 14px;
  color: #151516;
  margin-bottom: 4px;
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.prod-spec {
  font-size: 12px;
  color: #9C9C9C;
  margin-bottom: 4px;
}

.prod-price-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 14px;
  color: #9C9C9C;
}

.price-small {
  color: #9C9C9C;
  font-family: Arial, sans-serif;
}

.total-summary {
  text-align: right;
  border-top: 1px dashed #eee;
  margin-top: 15px;
  padding-top: 10px;
  font-size: 12px;
  color: #999;
}

.total-price {
  color: #E02E24;
  font-weight: bold;
}

.manager-tools {
  margin: 20px 12px;
  text-align: center;
  display: flex;
  gap: 10px;
}

.btn-outline {
  flex: 1;
  background: #fff;
  border: 1px solid #E02E24;
  color: #E02E24;
  padding: 10px 0;
  border-radius: 4px;
  font-size: 14px;
  cursor: pointer;
}

.btn-outline:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-poster {
  background: #E02E24;
  color: #fff;
}

.poster-modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.8);
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
  border-radius: 10px;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
}

.poster-tip {
  color: #fff;
  margin-top: 15px;
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
  border: 3px solid #f2d2d0;
  border-top-color: #E02E24;
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
  background: #f4f4f4;
  padding-bottom: 20px;
}

.poster-qr-wrap {
  text-align: center;
  padding-bottom: 20px;
}

.poster-qr {
  display: inline-block;
}

.poster-qr-tip,
.poster-more-tip {
  font-size: 12px;
  color: #999;
  margin-top: 10px;
  text-align: center;
}

@keyframes sharePreparingSpin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
