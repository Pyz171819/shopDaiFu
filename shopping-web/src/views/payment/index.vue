<template>
  <div class="payment-page">
    <section class="payment-shell">
      <header class="summary-card">
        <div class="summary-title">
          <span class="cart-icon">🛒</span>
          <span>订单总金额</span>
        </div>
        <div class="summary-price">
          <strong>¥{{ formatPrice(paymentSummary.amount) }}</strong>
          <span>（共 {{ paymentSummary.count }} 件商品）</span>
        </div>
      </header>

      <section class="pay-grid" v-loading="loading">
        <article
            v-for="option in paymentOptions"
            :key="option.id"
            class="pay-card"
            :class="{ active: selectedTpl === option.tpl }"
        >
          <div class="pay-card-banner">
            <img
                v-if="option.shareImage"
                :src="getTemplateImage(option.shareImage)"
                class="pay-card-image"
                :alt="option.title"
            >
            <div v-else class="pay-card-image-empty">
              {{ option.title }}
            </div>
            <div class="pay-card-overlay">
              <strong>{{ option.title }}</strong>
              <span>{{ option.subtitle }}</span>
            </div>
          </div>

          <button
              class="use-btn"
              type="button"
              @click="handleUseTpl(option)"
          >
            {{ option.actionText }}
          </button>
        </article>

        <div v-if="!loading && paymentOptions.length === 0" class="empty-wrap">
          暂无可用卡片模板
        </div>
      </section>

      <footer class="page-footer">
        <button class="back-btn" type="button" @click="$router.push({ name: 'home' })">
          返回商品页
        </button>
        <button 
          class="confirm-btn" 
          type="button" 
          @click="handleConfirm"
          :disabled="!selectedTpl || submitting"
        >
          {{ submitting ? '创建订单中...' : (selectedTpl ? `已选择：${selectedTplTitle}，点击去支付` : '请选择代付风格') }}
        </button>
      </footer>

      <button class="floating-home" type="button" @click="$router.push({ name: 'home' })">⌂</button>
    </section>
  </div>
</template>

<script>
import { getPaymentSummary } from '../../utils/app-state'
import { listAllShareCardConfig } from '@/api/shareCardConfig'
import { createOrder } from '@/api/order'

export default {
  name: 'PaymentPage',
  data() {
    return {
      loading: false,
      submitting: false,
      paymentSummary: getPaymentSummary(),
      selectedTpl: '',
      paymentOptions: []
    }
  },
  computed: {
    selectedTplTitle() {
      const current = this.paymentOptions.find(item => item.tpl === this.selectedTpl)
      return current ? current.title : ''
    }
  },
  created() {
    this.paymentSummary = getPaymentSummary()
    this.loadShareCardConfigs()
  },
  methods: {
    async loadShareCardConfigs() {
      this.loading = true
      try {
        const res = await listAllShareCardConfig()
        const list = Array.isArray(res.data) ? res.data : []
        this.paymentOptions = this.buildPaymentOptions(list)

        if (this.paymentOptions.length > 0) {
          this.selectedTpl = this.paymentOptions[0].tpl
        }
      } catch (error) {
        console.error('加载卡片模板失败', error)
        this.$message.error('加载卡片模板失败')
      } finally {
        this.loading = false
      }
    },

    buildPaymentOptions(list) {
      return (list || [])
          .sort((a, b) => Number(a.sort || 0) - Number(b.sort || 0))
          .map((item) => {
            return {
              id: item.id,
              tpl: item.tpl || '',
              title: item.cardName || '未命名模板',
              subtitle: item.shareSubtitle || '代付模板',
              actionText: '点击使用',
              shareTitle: item.shareTitle || '',
              shareDesc: item.shareDesc || '',
              shareImage: item.shareImage || '',
              useProductImage: Number(item.useProductImage || 0),
              sort: Number(item.sort || 0)
            }
          })
    },

    getTemplateImage(image) {
      if (!image) return ''

      // 如果已经是完整的 URL，直接返回
      if (/^https?:\/\//i.test(image)) {
        return image
      }

      // 使用 /api 前缀，让 vue.config.js 的代理处理
      // 这样在开发环境和生产环境都能正常工作
      if (image.startsWith('/')) {
        return `/api${image}`
      }

      return `/api/${image}`
    },

    handleUseTpl(option) {
      this.selectedTpl = option.tpl
      this.$message.success(`已选择模板：${option.title}`)
    },

    async handleConfirm() {
      if (!this.selectedTpl) {
        this.$message.warning('请选择代付风格')
        return
      }

      const summary = getPaymentSummary()
      if (!summary || !summary.items || summary.items.length === 0) {
        this.$message.warning('订单商品不能为空')
        return
      }

      const current = this.paymentOptions.find(item => item.tpl === this.selectedTpl)

      
      if (!current) {
        this.$message.warning('未找到选中的模板')
        return
      }

      // 构建订单参数
      const orderData = {
        items: summary.items.map(item => ({
          productId: item.id,
          quantity: item.quantity
        })),
        template: current.title || '',
        tpl: current.tpl || ''
      }

      this.submitting = true
      try {
        const res = await createOrder(orderData)

        if (res.code !== 200) {
          throw new Error(res.msg || '创建订单失败')
        }

        const order = res.data
        if (!order || !order.id) {
          throw new Error('订单创建成功，但未返回订单信息')
        }

        const outTradeNo = order.outTradeNo

        this.$message.success('订单创建成功')

        // 清除购物车数据
        // localStorage.removeItem('shopping-control-home-cart')
        // localStorage.removeItem('shopping-control-payment-summary')

        // 在微信环境中，配置分享
        try {
          const { isWechat } = await import('@/utils/device')
          if (isWechat()) {
            await this.configWechatShare(order, current)
          }
        } catch (error) {
          console.error('[Payment] 配置微信分享失败:', error)
          // 不影响跳转
        }

        // 跳转到支付页面
        setTimeout(() => {
          this.$router.push({ 
            name: 'cashier',
            query: { outTradeNo }
          })
        }, 500)

      } catch (error) {
        this.$message.error(error.message || '创建订单失败，请稍后重试')
      } finally {
        this.submitting = false
      }
    },

    async configWechatShare(order, templateConfig) {
      try {
        console.log('[Payment] 开始配置微信分享')
        
        const { getJsapiSignature } = await import('@/api/wechat')
        const { initWechatConfig, setShareToFriend } = await import('@/utils/wechat')

        const signUrl = window.location.href.split('#')[0]
        const res = await getJsapiSignature(signUrl)

        if (res.code !== 200 || !res.data) {
          throw new Error(res.msg || '获取微信签名失败')
        }

        const wx = await initWechatConfig(res.data)

        console.log('[Payment] wx.ready 触发，开始设置分享')

        // 构建分享链接
        const shareLink = `${window.location.origin}/share/cashier?outTradeNo=${encodeURIComponent(order.outTradeNo)}&from=share`
        
        // 获取分享图片
        let imgUrl = ''
        if (String(templateConfig.useProductImage) === '1') {
          // 使用商品图
          const items = order.items ? (typeof order.items === 'string' ? JSON.parse(order.items) : order.items) : []
          if (items.length > 0 && items[0].image) {
            const image = items[0].image
            if (image.startsWith('http://') || image.startsWith('https://')) {
              imgUrl = image
            } else {
              imgUrl = window.location.origin + (image.startsWith('/') ? `/api${image}` : `/api/${image}`)
            }
          }
        } else {
          // 使用固定图
          if (templateConfig.shareImage) {
            const shareImage = templateConfig.shareImage
            if (shareImage.startsWith('http://') || shareImage.startsWith('https://')) {
              imgUrl = shareImage
            } else {
              imgUrl = window.location.origin + '/api' + shareImage
            }
          }
        }

        const shareData = {
          title: templateConfig.shareTitle || '代付订单',
          desc: templateConfig.shareDesc || `帮我付款吧，金额：¥${order.money}`,
          link: shareLink,
          imgUrl: imgUrl
        }

        console.log('[Payment] 分享数据:', shareData)

        setShareToFriend(shareData)
        
        console.log('[Payment] 微信分享配置完成')
      } catch (error) {
        console.error('[Payment] 配置微信分享失败:', error)
        throw error
      }
    },

    formatPrice(value) {
      return Number(value || 0).toFixed(2)
    }
  }
}
</script>

<style scoped>
* {
  box-sizing: border-box;
}

.payment-page {
  min-height: 100vh;
  padding: 14px;
  background: #eef2f8;
}

.payment-shell {
  min-height: calc(100vh - 28px);
  position: relative;
  display: flex;
  flex-direction: column;
}

.summary-card {
  padding: 10px 14px;
  border-radius: 10px;
  background: #ffffff;
  box-shadow: 0 6px 18px rgba(53, 71, 103, 0.06);
}

.summary-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 16px;
  color: #7f8796;
}


.summary-price {
  margin-top: 8px;
  display: flex;
  align-items: baseline;
  gap: 10px;
}

.summary-price strong {
  font-size: 30px;
  color: #ff4f00;
}

.summary-price span {
  color: #7f8796;
}

.pay-grid {
  margin-top: 12px;
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 10px;
  padding-bottom: 100px;
}

.pay-card {
  border-radius: 12px;
  overflow: hidden;
  background: #ffffff;
  box-shadow: 0 6px 16px rgba(31, 40, 61, 0.07);
  border: 2px solid transparent;
  transition: all 0.2s ease;
}

.pay-card:hover {
  transform: translateY(-2px);
}

.pay-card.active {
  border-color: #2ff5ff;
  box-shadow: 0 8px 20px rgba(47, 245, 255, 0.18);
}

.pay-card-banner {
  padding: 8px 8px 0;
  position: relative;
}

.pay-card-image {
  width: 100%;
  height: 84px;
  object-fit: cover;
  border-radius: 9px;
  display: block;
  background: #f5f7fa;
}

.pay-card-image-empty {
  width: 100%;
  height: 84px;
  border-radius: 9px;
  background: #f5f7fa;
  color: #909399;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  padding: 8px;
  text-align: center;
}

.pay-card-overlay {
  position: absolute;
  bottom: 0;
  left: 8px;
  right: 8px;
  padding: 8px 6px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 2px;
  background: linear-gradient(to top, rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.4), transparent);
  border-radius: 0 0 9px 9px;
}

.pay-card-overlay strong {
  font-size: 15px;
  color: #ffffff;
  line-height: 1.25;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

.pay-card-overlay span {
  font-size: 12px;
  color: #ffffff;
  line-height: 1.25;
  opacity: 0.95;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.use-btn {
  width: calc(100% - 16px);
  margin: 8px;
  height: 30px;
  border: 1px solid #edf0f6;
  border-radius: 999px;
  background: #ffffff;
  color: #7d8594;
  font-size: 12px;
  cursor: pointer;
}

.pay-card-top strong {
  font-size: 16px;
}

.pay-card-top span {
  font-size: 14px;
  font-weight: 600;
}

.pay-icon {
  font-size: 28px;
  line-height: 1;
}

.empty-wrap {
  grid-column: 1 / -1;
  padding: 40px 0;
  text-align: center;
  color: #909399;
  background: #fff;
  border-radius: 12px;
}

.page-footer {
  margin-top: auto;
  padding: 18px 0 8px;
  display: flex;
  justify-content: center;
  gap: 16px;
}

.back-btn,
.confirm-btn,
.floating-home {
  border: none;
  cursor: pointer;
}

.back-btn {
  height: 48px;
  padding: 0 24px;
  border-radius: 24px;
  background: #ffffff;
  color: #4b5464;
  box-shadow: 0 8px 20px rgba(31, 40, 61, 0.08);
}

.confirm-btn {
  min-width: 430px;
  height: 48px;
  border-radius: 24px;
  background: #545460;
  color: #2ff5ff;
  font-size: 20px;
  font-weight: 700;
}

.confirm-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.floating-home {
  position: fixed;
  right: 22px;
  bottom: 22px;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: #ffffff;
  box-shadow: 0 10px 24px rgba(31, 40, 61, 0.12);
  color: #4b5464;
  font-size: 22px;
}

@media (max-width: 1100px) {
  .pay-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

@media (max-width: 820px) {
  .payment-page {
    padding: 12px;
  }

  .payment-shell {
    min-height: calc(100vh - 24px);
  }

  .summary-price strong {
    font-size: 28px;
  }

  .pay-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .page-footer {
    flex-direction: column;
    align-items: center;
  }

  .confirm-btn {
    min-width: 0;
    width: 100%;
  }

  .back-btn {
    width: 100%;
  }
}

@media (max-width: 560px) {
  .payment-page {
    padding: 0;
  }

  .payment-shell {
    min-height: 100vh;
    padding: 10px 10px 108px;
  }

  .summary-card {
    padding: 12px;
    border-radius: 12px;
  }

  .summary-title {
    font-size: 13px;
  }

  .summary-price {
    gap: 6px;
    flex-wrap: wrap;
  }

  .summary-price strong {
    font-size: 24px;
  }

  .summary-price span {
    font-size: 12px;
  }

  .pay-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
    padding-bottom: 126px;
  }

  .pay-card {
    border-radius: 12px;
  }

  .pay-card-banner {
    padding: 7px 7px 0;
  }

  .pay-card-image,
  .pay-card-image-empty {
    height: 72px;
    border-radius: 8px;
  }

  .pay-card-overlay {
    left: 7px;
    right: 7px;
    padding: 7px 5px;
    border-radius: 0 0 8px 8px;
  }

  .pay-card-overlay strong {
    font-size: 13px;
  }

  .pay-card-overlay span {
    font-size: 10px;
  }

  .pay-card-top {
    min-height: 92px;
    padding: 10px 8px;
    gap: 4px;
  }

  .pay-card-top strong {
    font-size: 14px;
  }

  .pay-card-top span {
    font-size: 11px;
  }

  .pay-icon {
    font-size: 22px;
  }

  .use-btn {
    width: calc(100% - 14px);
    height: 28px;
    margin: 7px;
    font-size: 11px;
  }

  .page-footer {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 20;
    padding: 12px 14px calc(12px + env(safe-area-inset-bottom));
    gap: 0;
    justify-content: center;
    pointer-events: none;
  }

  .back-btn {
    display: none;
  }

  .confirm-btn {
    width: min(100%, 260px);
    min-width: 0;
    height: 46px;
    border-radius: 23px;
    font-size: 16px;
    color: #46f2ff;
    box-shadow: 0 18px 30px rgba(26, 30, 42, 0.28);
    pointer-events: auto;
  }

  .floating-home {
    right: 16px;
    bottom: calc(76px + env(safe-area-inset-bottom));
    width: 42px;
    height: 42px;
    font-size: 18px;
    z-index: 21;
  }
}

@media (max-width: 380px) {
  .payment-shell {
    padding: 8px 8px 106px;
  }

  .summary-card {
    padding: 10px;
  }

  .summary-title {
    font-size: 12px;
  }

  .summary-price strong {
    font-size: 22px;
  }

  .summary-price span {
    font-size: 11px;
  }

  .pay-grid {
    gap: 7px;
  }

  .pay-card-image,
  .pay-card-image-empty {
    height: 66px;
  }

  .pay-card-top {
    min-height: 86px;
    padding: 10px 6px;
  }

  .pay-card-top strong {
    font-size: 13px;
  }

  .pay-card-top span {
    font-size: 10px;
  }

  .use-btn {
    height: 28px;
    font-size: 10px;
  }

  .confirm-btn {
    width: min(100%, 240px);
    height: 44px;
    font-size: 15px;
  }
}
</style>
