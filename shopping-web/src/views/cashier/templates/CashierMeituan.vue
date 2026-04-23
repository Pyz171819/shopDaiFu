<template>
  <div class="cashier-default">
    <div class="user-header">
      <img :src="userAvatar" class="avatar" alt="头像">
      <div class="user-info">
        <div class="nick">{{ userNick }}</div>
        <div class="slogan">{{ userSlogan }}</div>
      </div>
    </div>

    <div class="card">
      <div v-if="isPaid" class="paid-state">
        <div class="paid-title">
          <svg viewBox="0 0 1024 1024" width="26" height="26" class="paid-icon">
            <path d="M512 0C229.23 0 0 229.23 0 512s229.23 512 512 512 512-229.23 512-512S794.77 0 512 0z" fill="#FFC300"/>
            <path d="M426.67 746.67a32 32 0 0 1-22.61-9.39l-213.33-213.33a32 32 0 1 1 45.27-45.27L426.67 669.33l362.66-362.66a32 32 0 0 1 45.27 45.27l-385.33 385.33a32 32 0 0 1-22.6 9.4z" fill="#FFFFFF"/>
          </svg>
          <span>美团用户已付款</span>
        </div>
        <div class="pay-amount">{{ order.money }}</div>
        <div class="paid-channel">微信支付</div>
      </div>

      <div v-else class="unpaid-state">
        <div class="pay-title">需付款</div>
        <div class="pay-amount">{{ order.money }}</div>
        <div v-if="!isExpired" class="timer-wrap">
          支付剩余时间
          <span class="timer-box">{{ minutes }}</span>
          :
          <span class="timer-box">{{ seconds }}</span>
        </div>
      </div>

      <div class="notice-box">
        <div class="notice-title">付款须知</div>
        <div>1. 代付订单创建成功后 15 分钟内未付款，订单会自动取消，可重新下单。</div>
        <div>2. 如订单后续发生退款，实付金额将原路退回代付人账户。</div>
      </div>

      <div v-if="isPaid">
        <a href="javascript:;" class="btn-main" @click="$router.push({ name: 'home' })">知道了</a>
      </div>
      <div v-else>
        <button v-if="isExpired" class="btn-main btn-disabled" disabled>
          订单已过期
        </button>

        <div v-else class="share-action-wrap">
          <button
            class="btn-main btn-poster"
            @click="handleGeneratePoster"
            :disabled="isGeneratingPoster"
          >
            {{ isGeneratingPoster ? '海报生成中...' : '生成海报分享' }}
          </button>

          <button
            class="btn-main btn-card-share"
            @click="handleCardShare"
            :disabled="isBindingWechatShare"
          >
            {{ isBindingWechatShare ? '正在准备分享...' : '微信卡片分享' }}
          </button>
        </div>
      </div>
    </div>

    <div class="card">
      <div v-if="isMultiItem">
        <div
          v-for="(item, index) in orderItems"
          :key="index"
          class="product-section"
        >
          <div class="shop-name-header">
            {{ item.shopName || '外卖订单' }}
          </div>
          <div class="product-row product-row-first">
            <img :src="getImageUrl(item.image)" class="p-img" alt="商品">
            <div class="p-info">
              <div class="p-name">{{ item.name }}</div>
              <div class="price-line">
                <span class="count-text">X {{ item.quantity || 1 }}</span>
                <span class="money-text">￥ {{ item.price }}</span>
              </div>
            </div>
          </div>
        </div>
        <div class="total-summary">
          共 {{ orderItems.length }} 件商品，合计
          <span class="money-strong">￥{{ order.money }}</span>
        </div>
      </div>

      <div v-else>
        <div class="shop-name-header">
          {{ shopName }}
        </div>
        <div class="product-row product-row-first product-row-last">
          <img :src="productImage" class="p-img" alt="商品">
          <div class="p-info">
            <div class="p-name">{{ productName }}</div>
            <div class="price-line">
              <span class="count-text">X 1</span>
              <span class="money-text">￥ {{ order.money }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showPosterModal" class="poster-modal" @click="closePosterModal">
      <div class="poster-modal-close">×</div>
      <img v-if="posterImageUrl" :src="posterImageUrl" class="poster-image" alt="海报">
      <div class="poster-tip">长按图片保存，分享给好友</div>
    </div>

    <div v-if="showSharePreparingModal" class="share-preparing-modal">
      <div class="share-preparing-card">
        <div class="share-preparing-spinner"></div>
        <div class="share-preparing-title">正在准备微信分享功能</div>
        <div class="share-preparing-text">请稍候，准备完成后再点击右上角三个点进行分享</div>
      </div>
    </div>

    <div ref="posterCanvas" class="poster-canvas">
      <div ref="posterQrContainer" class="poster-qr"></div>
    </div>
  </div>
</template>

<script>
import html2canvas from 'html2canvas'
import QRCode from 'qrcodejs2'

export default {
  name: 'CashierMeituan',
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
    isMultiItem() {
      return this.orderItems.length > 1
    },
    productName() {
      return this.order.orderName || '商品订单'
    },
    productImage() {
      if (this.orderItems.length > 0) {
        return this.getImageUrl(this.orderItems[0].image)
      }
      return ''
    },
    shopName() {
      if (this.orderItems.length > 0 && this.orderItems[0].shopName) {
        return this.orderItems[0].shopName
      }
      return '外卖订单'
    },
    userNick() {
      if (this.userInfo && this.userInfo.nickname) {
        return this.userInfo.nickname
      }
      return '美团用户'
    },
    userAvatar() {
      if (this.userInfo && this.userInfo.avatar) {
        return this.getImageUrl(this.userInfo.avatar)
      }
      return 'https://img.yzcdn.cn/vant/cat.jpeg'
    },
    userSlogan() {
      return 'Hi~你和我的距离只差一顿外卖'
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
    document.title = '美团外卖代付'
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
            nickname: profile.nickName || profile.nickname || profile.userName || profile.username || '美团用户',
            avatar: profile.avatar || ''
          }
        }
      } catch (error) {
        console.error('获取用户信息失败:', error)
        this.userInfo = {
          nickname: '美团用户',
          avatar: ''
        }
      }
    },

    async fetchShareCardConfig() {
      if (!this.order.tpl) {
        return
      }

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
        if (!posterCanvas) {
          throw new Error('海报容器未找到')
        }

        posterCanvas.style.backgroundImage = 'url(/mthbs.png)'

        const qrContainer = this.$refs.posterQrContainer
        if (!qrContainer) {
          throw new Error('二维码容器未找到')
        }

        qrContainer.innerHTML = ''

        const qrSize = Math.floor(750 * 0.25)
        new QRCode(qrContainer, {
          text: this.getPosterQrUrl(),
          width: qrSize,
          height: qrSize,
          colorLight: '#ffffff',
          correctLevel: QRCode.CorrectLevel.H
        })

        await new Promise(resolve => setTimeout(resolve, 800))

        const canvas = await html2canvas(posterCanvas, {
          useCORS: true,
          scale: 1,
          allowTaint: false,
          backgroundColor: null
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
        title: this.shareCardConfig?.shareTitle || '美团外卖代付',
        desc: this.shareCardConfig?.shareDesc || this.userSlogan,
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

.cashier-default {
  min-height: 100vh;
  background-color: #F5F5F5;
  padding-bottom: 20px;
}

.user-header {
  padding: 20px 15px;
  display: flex;
  align-items: center;
}

.avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  object-fit: cover;
  margin-right: 12px;
  border: 1px solid #fff;
}

.user-info .nick {
  font-size: 16px;
  font-weight: bold;
  margin-bottom: 4px;
  color: #222;
}

.user-info .slogan {
  font-size: 12px;
  color: #666;
}

.card {
  background: #fff;
  border-radius: 12px;
  margin: 0 12px 12px;
  padding: 25px 20px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
}

.paid-state,
.unpaid-state {
  text-align: center;
}

.paid-title {
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 12px;
  font-size: 20px;
  font-weight: bold;
  color: #333;
}

.paid-icon {
  margin-right: 10px;
}

.paid-channel {
  color: #888;
  font-size: 14px;
  margin-bottom: 20px;
}

.pay-title {
  font-size: 16px;
  font-weight: bold;
  margin-bottom: 15px;
  color: #333;
}

.pay-amount {
  text-align: center;
  font-size: 38px;
  font-weight: bold;
  margin-bottom: 5px;
  font-family: Arial, sans-serif;
}

.pay-amount::before {
  content: '￥';
  font-size: 22px;
  margin-right: 4px;
}

.timer-wrap {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-bottom: 30px;
  font-size: 14px;
  color: #333;
}

.timer-box {
  background: #333;
  color: #fff;
  padding: 2px 4px;
  border-radius: 4px;
  margin: 0 4px;
  font-weight: bold;
}

.notice-box {
  background: #FEF8E5;
  border-radius: 8px;
  padding: 15px;
  font-size: 12px;
  color: #8B572A;
  line-height: 1.8;
  margin-bottom: 15px;
}

.notice-title {
  color: #8B572A;
  margin-bottom: 5px;
  font-weight: bold;
  font-size: 13px;
}

.btn-main {
  display: block;
  width: 100%;
  background: #FDD934;
  color: #000;
  font-size: 16px;
  font-weight: bold;
  text-align: center;
  padding: 14px 0;
  border-radius: 25px;
  text-decoration: none;
  border: none;
  cursor: pointer;
  margin-top: 15px;
}

.btn-disabled {
  background: #ccc !important;
  cursor: not-allowed !important;
}

.share-action-wrap {
  margin-top: 10px;
}

.btn-poster:disabled,
.btn-card-share:disabled {
  opacity: 0.7;
  cursor: not-allowed !important;
}

.btn-poster {
  background: #fff;
  border: 1px solid #FDD934;
  color: #F5A623;
  margin-top: 10px;
}

.btn-card-share {
  background: #fff;
  border: 1px solid #07c160;
  color: #07c160;
  margin-top: 10px;
}

.product-section {
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 2px solid #f0f0f0;
}

.product-section:last-child {
  margin-bottom: 0;
  border-bottom: none;
  padding-bottom: 0;
}

.shop-name-header {
  font-size: 13px;
  color: #999;
  font-weight: 600;
  padding: 8px 12px;
  background: #fafafa;
  border-radius: 6px;
  margin-bottom: 12px;
  border-left: 3px solid #FDD934;
}

.total-summary {
  text-align: right;
  margin-top: 15px;
  padding-top: 15px;
  border-top: 2px dashed #e5e5e5;
  font-size: 14px;
  color: #666;
  font-weight: 500;
}

.money-strong {
  color: #000;
  font-weight: bold;
}

.product-row {
  display: flex;
  align-items: center;
  margin-top: 15px;
  padding-bottom: 15px;
  border-bottom: 1px dashed #f5f5f5;
}

.product-row-first {
  margin-top: 0;
}

.product-row-last {
  border-bottom: none;
  padding-bottom: 0;
}

.p-img {
  width: 60px;
  height: 60px;
  background: #f8f8f8;
  border-radius: 6px;
  margin-right: 12px;
  object-fit: cover;
  flex-shrink: 0;
}

.p-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.p-name {
  font-size: 14px;
  color: #333;
  line-height: 1.4;
  margin-bottom: 4px;
  height: 40px;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.price-line {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.count-text {
  color: #999;
  font-size: 12px;
}

.money-text {
  font-weight: bold;
  font-family: Arial, sans-serif;
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
  border: 3px solid #f7ebba;
  border-top-color: #F5A623;
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
  width: 750px;
  height: 1334px;
  background-color: #fff;
  background-repeat: no-repeat;
  background-size: 100% 100%;
  overflow: hidden;
}

.poster-qr {
  position: absolute;
  bottom: 5%;
  right: 8%;
  z-index: 99;
  border: 4px solid #fff;
  border-radius: 4px;
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

@keyframes sharePreparingSpin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
