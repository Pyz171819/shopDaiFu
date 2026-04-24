import html2canvas from 'html2canvas'
import QRCode from 'qrcodejs2'

export default {
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
            nickname: profile.nickName || profile.nickname || profile.userName || profile.username || '',
            avatar: profile.avatar || ''
          }
        }
      } catch (error) {
        console.error('获取用户信息失败:', error)
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
          this.applyDocumentTitle()
        }
      } catch (error) {
        console.error('获取分享卡片配置失败:', error)
      }
    },

    getPageTitle() {
      return this.shareCardConfig?.cardName || this.pageTitle || this.getDefaultShareTitle()
    },

    applyDocumentTitle() {
      const title = this.getPageTitle()
      if (title) {
        document.title = title
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

    getDefaultShareTitle() {
      return this.productName
    },

    getDefaultShareDesc() {
      return `帮我付款吧，金额：${this.order.money}`
    },

    createWechatShareData() {
      return {
        title: this.shareCardConfig?.shareTitle || this.getDefaultShareTitle(),
        desc: this.shareCardConfig?.shareDesc || this.getDefaultShareDesc(),
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

    getPosterQrOptions() {
      return {
        width: 120,
        height: 120,
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.H
      }
    },

    getPosterCanvasOptions() {
      return {
        useCORS: true,
        scale: 2,
        allowTaint: false,
        backgroundColor: '#ffffff'
      }
    },

    waitForPosterQrImage(qrContainer) {
      return new Promise((resolve) => {
        const qrCanvas = qrContainer.querySelector('canvas')

        if (qrCanvas) {
          const canvasImage = document.createElement('img')
          canvasImage.src = qrCanvas.toDataURL('image/png')
          canvasImage.alt = '二维码'
          canvasImage.style.display = 'block'
          canvasImage.style.width = `${qrCanvas.width}px`
          canvasImage.style.height = `${qrCanvas.height}px`
          canvasImage.style.border = '0'
          qrContainer.innerHTML = ''
          qrContainer.appendChild(canvasImage)
          if (typeof canvasImage.decode === 'function') {
            canvasImage.decode().then(() => resolve()).catch(() => resolve())
            return
          }
          if (canvasImage.complete) {
            resolve()
            return
          }
          canvasImage.onload = () => resolve()
          canvasImage.onerror = () => resolve()
          return
        }

        const targetImage = qrContainer.querySelector('img')
        if (!targetImage) {
          resolve()
          return
        }

        targetImage.style.display = 'block'
        if (targetImage.complete) {
          resolve()
          return
        }

        targetImage.onload = () => resolve()
        targetImage.onerror = () => resolve()
      })
    },

    async renderPosterQr(qrContainer) {
      const options = this.getPosterQrOptions()
      qrContainer.innerHTML = ''
      new QRCode(qrContainer, {
        text: this.getPosterQrUrl(),
        ...options
      })

      await this.$nextTick()
      await new Promise(resolve => setTimeout(resolve, 100))
      await this.waitForPosterQrImage(qrContainer)
      await new Promise(resolve => setTimeout(resolve, 100))
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
        if (!this.shareCardConfig) {
          await this.fetchShareCardConfig()
        }
        await this.$nextTick()
        await new Promise(resolve => setTimeout(resolve, 100))

        const posterCanvas = this.$refs.posterCanvas
        const qrContainer = this.$refs.posterQrContainer
        if (!posterCanvas || !qrContainer) {
          throw new Error('海报容器不存在')
        }

        await this.renderPosterQr(qrContainer)

        const canvas = await html2canvas(posterCanvas, this.getPosterCanvasOptions())
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
