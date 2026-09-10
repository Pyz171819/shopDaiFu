<template>
  <div class="home-page">
    <section class="phone-shell">
      <header class="top-bar">
        <div class="search-bar">
          <span class="search-icon"></span>
          <input
            v-model.trim="searchKeyword"
            class="search-input"
            type="text"
            placeholder="搜索商品名称..."
            @keyup.enter="handleSearch"
            @input="handleSearchInput"
          />
          <button class="search-btn" type="button" @click="handleSearch">搜索</button>
        </div>
      </header>

      <section v-if="showHeroArea" class="hero-stack">
        <div
          v-if="showSlidesSection"
          class="hero-slider"
          @mouseenter="stopSlideAutoplay"
          @mouseleave="startSlideAutoplay"
        >
          <div class="slider-track" :style="sliderTrackStyle">
            <div
              v-for="slide in slides"
              :key="slide.id"
              class="slide-card"
              role="button"
              tabindex="0"
              @click="handleSlideClick(slide)"
              @keydown.enter.prevent="handleSlideClick(slide)"
              @keydown.space.prevent="handleSlideClick(slide)"
            >
              <img
                :src="getResourceUrl(slide.img)"
                :alt="slide.title || '首页轮播图'"
                class="slide-image"
              />
              <div class="slide-overlay">
                <span class="slide-tag">精选推荐</span>
                <strong class="slide-title">{{ slide.title || "新品上架" }}</strong>
                <span class="slide-link">{{ slide.url ? "点击查看详情" : "首页活动展示" }}</span>
              </div>
            </div>
          </div>

          <div v-if="slides.length > 1" class="slider-dots">
            <button
              v-for="(slide, index) in slides"
              :key="slide.id"
              :class="['slider-dot', { active: activeSlideIndex === index }]"
              type="button"
              @click="setActiveSlide(index)"
            ></button>
          </div>
        </div>

        <div v-if="showNoticeSection" class="notice-banner">
          <span class="notice-badge">公告</span>
          <div class="notice-marquee">
            <div :class="['notice-track', { animated: shouldAnimateNotice }]" :style="noticeTrackStyle">
              <span class="notice-text">{{ noticeText }}</span>
              <span v-if="shouldAnimateNotice" class="notice-text clone" aria-hidden="true">
                {{ noticeText }}
              </span>
            </div>
          </div>
        </div>
      </section>

      <main class="content-area">
        <aside class="category-list">
          <button
            v-for="category in categories"
            :key="category.id"
            :class="['category-item', { active: activeCategoryId === category.id }]"
            type="button"
            @click="handleCategoryClick(category)"
          >
            {{ category.name }}
          </button>
        </aside>

        <section class="goods-panel">
          <div v-if="goods.length" class="goods-list">
            <article v-for="item in goods" :key="item.id" class="goods-card">
              <div class="goods-cover">
                <img
                  v-if="item.image"
                  :src="getResourceUrl(item.image)"
                  alt="商品图片"
                  class="goods-cover-img"
                />
                <span v-else>{{ item.short }}</span>
              </div>

              <div class="goods-main">
                <h3>{{ item.name }}</h3>
                <strong>¥{{ formatPrice(item.price) }}</strong>
              </div>

              <div class="goods-actions">
                <button class="buy-btn" type="button" @click="buyNow(item)">购买</button>
                <div v-if="item.quantity > 0" class="quantity-control">
                  <button
                    class="quantity-btn"
                    type="button"
                    aria-label="减少商品数量"
                    @click="decreaseQuantity(item)"
                  >
                    -
                  </button>
                  <span class="quantity-value">{{ item.quantity }}</span>
                  <button
                    class="quantity-btn"
                    type="button"
                    aria-label="增加商品数量"
                    @click="increaseQuantity(item)"
                  >
                    +
                  </button>
                </div>
                <button
                  v-else
                  class="plus-btn"
                  type="button"
                  aria-label="加入购物车"
                  @click="increaseQuantity(item)"
                >
                  <span class="plus-sign">+</span>
                </button>
              </div>
            </article>
          </div>

          <div v-else class="goods-empty">
            <strong>当前没有可展示商品</strong>
            <span>可以切换分类，或尝试重新搜索关键词。</span>
          </div>
        </section>
      </main>

      <footer class="shop-summary">
        <div class="shop-pill">
          <div class="shop-profile">
            <div class="shop-avatar-shell">
              <img
                v-if="userProfile.avatar"
                :src="getAvatarUrl(userProfile.avatar)"
                alt="头像"
                class="shop-avatar-image"
              />
              <div v-else class="shop-avatar-fallback">
                {{ userProfile.avatarText || "购" }}
              </div>
            </div>

            <div class="shop-profile-meta">
              <span class="shop-profile-label">WELCOME</span>
              <strong class="shop-profile-name">{{ userProfile.nickname || "用户中心" }}</strong>
            </div>
          </div>

          <button class="profile-btn" type="button" @click="$router.push({ name: 'profile' })">
            个人中心 &gt;
          </button>
        </div>

        <div class="cart-bar">
          <button
            class="cart-total"
            type="button"
            :aria-label="`查看购物车，共 ${totalCount} 件商品`"
            @click="openCartDrawer"
          >
            <span>合计</span>
            <strong>¥{{ formatPrice(totalAmount) }}</strong>
            <em>({{ totalCount }})</em>
          </button>
          <div class="cart-actions">
            <button
              class="clear-btn"
              type="button"
              :disabled="!totalCount"
              @click="showClearConfirm = true"
            >
              清空
            </button>
            <button class="settle-btn" type="button" :disabled="!totalCount" @click="goCheckout">
              去结算
            </button>
          </div>
        </div>
      </footer>

      <div v-if="showCartDrawer" class="cart-drawer-mask" @click.self="closeCartDrawer">
        <aside
          class="cart-drawer"
          role="dialog"
          aria-modal="true"
          aria-labelledby="cart-drawer-title"
        >
          <header class="cart-drawer-header">
            <div>
              <h2 id="cart-drawer-title">购物车</h2>
              <span>{{ totalCount }} 件商品</span>
            </div>
            <button
              class="drawer-close-btn"
              type="button"
              aria-label="关闭购物车"
              @click="closeCartDrawer"
            >
              ×
            </button>
          </header>

          <div v-if="selectedItems.length" class="cart-drawer-list">
            <article v-for="item in selectedItems" :key="item.id" class="cart-drawer-item">
              <div class="drawer-item-cover">
                <img
                  v-if="item.image"
                  :src="getResourceUrl(item.image)"
                  :alt="item.name"
                />
                <span v-else>{{ item.short }}</span>
              </div>
              <div class="drawer-item-main">
                <h3>{{ item.name }}</h3>
                <div class="drawer-item-meta">
                  <span>¥{{ formatPrice(item.price) }} / 件</span>
                  <strong>¥{{ formatPrice(item.price * item.quantity) }}</strong>
                </div>
                <div class="drawer-item-actions">
                  <div class="quantity-control drawer-quantity-control">
                    <button
                      class="quantity-btn"
                      type="button"
                      aria-label="减少商品数量"
                      @click="decreaseQuantity(item)"
                    >
                      -
                    </button>
                    <span class="quantity-value">{{ item.quantity }}</span>
                    <button
                      class="quantity-btn"
                      type="button"
                      aria-label="增加商品数量"
                      @click="increaseQuantity(item)"
                    >
                      +
                    </button>
                  </div>
                  <button
                    class="remove-item-btn"
                    type="button"
                    aria-label="删除商品"
                    @click="removeCartItem(item)"
                  >
                    删除
                  </button>
                </div>
              </div>
            </article>
          </div>

          <div v-else class="cart-drawer-empty">
            <strong>购物车还是空的</strong>
            <span>先挑几件喜欢的商品吧</span>
          </div>

          <footer class="cart-drawer-footer">
            <div class="drawer-total">
              <span>合计</span>
              <strong>¥{{ formatPrice(totalAmount) }}</strong>
            </div>
            <button class="drawer-settle-btn" type="button" :disabled="!totalCount" @click="goCheckout">
              去结算
            </button>
          </footer>
        </aside>
      </div>

      <div v-if="showClearConfirm" class="modal-mask" @click.self="showClearConfirm = false">
        <div class="confirm-modal">
          <h3>确认清空购物车？</h3>
          <p>已选商品数量和金额会被重置，确认后不可恢复。</p>
          <div class="confirm-actions">
            <button class="modal-btn secondary" type="button" @click="showClearConfirm = false">
              取消
            </button>
            <button class="modal-btn danger" type="button" @click="clearCart">确认清空</button>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import { getUserSimpleInfo } from "../../api/auth";
import {
  getHomeCartState,
  setHomeCartState,
  setPaymentSummary
} from "../../utils/app-state";
import {
  getHomeConfig,
  listCategory,
  listHomeSlides,
  listProductByCategory,
  searchHomeProducts
} from "../../api/home";

export default {
  name: "HomePage",
  data() {
    const cartState = getHomeCartState();

    return {
      userProfile: {},
      avatarVersion: Date.now(),
      activeCategoryId: null,
      showClearConfirm: false,
      showCartDrawer: false,
      searchKeyword: "",
      categories: [],
      goods: [],
      slides: [],
      activeSlideIndex: 0,
      slideTimer: null,
      slideAutoplayDelay: 3500,
      homeConfig: {
        siteNotice: "",
        showNotice: "0",
        showSlides: "0"
      },
      cartItems: cartState.items,
      savedActiveCategoryId: cartState.activeCategoryId
    };
  },
  created() {
    this.fetchUserProfile();
    this.loadHomeDecorations();
    this.getCategoryList();
  },
  activated() {
    this.fetchUserProfile();
    this.startSlideAutoplay();
  },
  deactivated() {
    this.stopSlideAutoplay();
  },
  beforeDestroy() {
    this.stopSlideAutoplay();
  },
  watch: {
    showSlidesSection(value) {
      if (value) {
        this.startSlideAutoplay();
      } else {
        this.stopSlideAutoplay();
      }
    },
    slides(list) {
      if (this.activeSlideIndex >= list.length) {
        this.activeSlideIndex = 0;
      }
      this.startSlideAutoplay();
    }
  },
  computed: {
    selectedItems() {
      return Object.values(this.cartItems)
        .filter(item => Number(item.quantity || 0) > 0)
        .map(item => ({
          id: item.id,
          name: item.name,
          price: Number(item.price || 0),
          quantity: Number(item.quantity || 0),
          image: item.image || "",
          short: item.name ? item.name.slice(0, 1) : "购"
        }));
    },
    totalCount() {
      return this.selectedItems.reduce((sum, item) => sum + Number(item.quantity || 0), 0);
    },
    totalAmount() {
      return this.selectedItems.reduce((sum, item) => {
        return sum + Number(item.price || 0) * Number(item.quantity || 0);
      }, 0);
    },
    noticeText() {
      return (this.homeConfig.siteNotice || "")
        .split(/\r?\n/)
        .map(item => item.trim())
        .filter(Boolean)
        .join("   ·   ");
    },
    shouldAnimateNotice() {
      return this.noticeText.length > 28;
    },
    noticeTrackStyle() {
      if (!this.shouldAnimateNotice) {
        return {};
      }

      return {
        animationDuration: `${Math.max(10, Math.ceil(this.noticeText.length / 2.8))}s`
      };
    },
    showNoticeSection() {
      return this.homeConfig.showNotice === "1" && Boolean(this.noticeText);
    },
    showSlidesSection() {
      return this.homeConfig.showSlides === "1" && this.slides.length > 0;
    },
    showHeroArea() {
      return this.showSlidesSection || this.showNoticeSection;
    },
    sliderTrackStyle() {
      return {
        transform: `translateX(-${this.activeSlideIndex * 100}%)`
      };
    }
  },
  methods: {
    async fetchUserProfile() {
      try {
        const res = await getUserSimpleInfo();
        const profile = res.user || res.data || res;

        if (!profile || !(profile.userId || profile.id)) {
          throw new Error("获取用户信息失败");
        }

        const nickname = profile.nickName || profile.nickname || "";
        const userName = profile.userName || profile.username || "";
        const avatar = profile.avatar || "";

        this.userProfile = {
          ...profile,
          userId: profile.userId || profile.id,
          id: profile.id || profile.userId,
          nickname,
          nickName: nickname,
          username: userName,
          userName,
          avatar,
          avatarText: (nickname || userName || "购")
            .slice(0, 1)
            .toUpperCase()
        };

        this.avatarVersion = Date.now();
      } catch (error) {
        this.userProfile = {};
      }
    },

    async loadHomeDecorations() {
      const [configResult, slidesResult] = await Promise.allSettled([
        getHomeConfig(),
        listHomeSlides()
      ]);

      if (configResult.status === "fulfilled") {
        const data = configResult.value.data || {};
        this.homeConfig = {
          siteNotice: data.site_notice || "",
          showNotice: String(data.show_notice || "0"),
          showSlides: String(data.show_slides || "0")
        };
      } else {
        this.homeConfig = {
          siteNotice: "",
          showNotice: "0",
          showSlides: "0"
        };
      }

      if (slidesResult.status === "fulfilled") {
        const list = Array.isArray(slidesResult.value.data) ? slidesResult.value.data : [];
        this.slides = list
          .map(item => ({
            id: item.id,
            title: item.title || "",
            img: item.img || "",
            url: item.url || "",
            sort: Number(item.sort || 0),
            status: String(item.status ?? "1"),
            delFlag: String(item.delFlag ?? "1")
          }))
          .filter(item => item.status === "1" && item.delFlag !== "0" && item.img)
          .sort((a, b) => b.sort - a.sort);
      } else {
        this.slides = [];
      }

      if (this.activeSlideIndex >= this.slides.length) {
        this.activeSlideIndex = 0;
      }

      this.startSlideAutoplay();
    },

    async getCategoryList() {
      try {
        const res = await listCategory();
        this.categories = (res.data || []).map(item => ({
          id: item.id,
          name: item.name
        }));

        if (this.categories.length > 0) {
          const matchedCategory = this.categories.find(
            item => item.id === this.savedActiveCategoryId
          );
          this.activeCategoryId = matchedCategory ? matchedCategory.id : this.categories[0].id;
          this.syncCartState();
          await this.getGoodsList();
        }
      } catch (error) {
        this.categories = [];
      }
    },

    async getGoodsList(options = {}) {
      const keyword = (options.keyword !== undefined ? options.keyword : this.searchKeyword || "").trim();
      const shouldUseCategory = keyword ? !options.ignoreCategory : true;

      if (shouldUseCategory && !this.activeCategoryId) {
        this.goods = [];
        return;
      }

      try {
        const res = keyword
          ? await searchHomeProducts({
              name: keyword,
              categoryId: shouldUseCategory ? this.activeCategoryId : undefined
            })
          : await listProductByCategory(this.activeCategoryId);

        this.goods = (res.data || []).map(item => ({
          id: item.id,
          categoryId: item.categoryId,
          name: item.name,
          price: Number(item.price || 0),
          quantity: Number((this.cartItems[item.id] && this.cartItems[item.id].quantity) || 0),
          image: item.image || "",
          short: item.name ? item.name.slice(0, 1) : "购"
        }));
      } catch (error) {
        this.goods = [];
      }
    },

    handleSearchInput() {
      if (!this.searchKeyword.trim()) {
        this.getGoodsList({ keyword: "" });
      }
    },

    handleSearch() {
      const keyword = this.searchKeyword.trim();

      if (!keyword) {
        this.getGoodsList({ keyword: "" });
        return;
      }

      this.getGoodsList({
        keyword,
        ignoreCategory: true
      });
    },

    handleCategoryClick(category) {
      const sameCategory = this.activeCategoryId === category.id;
      this.activeCategoryId = category.id;
      this.syncCartState();
      if (sameCategory && !this.searchKeyword.trim()) return;
      this.getGoodsList();
    },

    syncCartState() {
      setHomeCartState({
        activeCategoryId: this.activeCategoryId,
        items: this.cartItems
      });
    },

    updateCartItem(item, quantity = item.quantity) {
      const itemId = String(item.id);
      const nextQuantity = Math.max(0, Math.floor(Number(quantity) || 0));
      const currentItem = this.cartItems[item.id] || this.cartItems[itemId] || {};

      if (nextQuantity > 0) {
        this.cartItems = {
          ...this.cartItems,
          [item.id]: {
            id: item.id,
            name: item.name || currentItem.name || "商品",
            price: Number(item.price ?? currentItem.price ?? 0),
            image: item.image || currentItem.image || "",
            quantity: nextQuantity
          }
        };
      } else {
        const nextCartItems = { ...this.cartItems };
        delete nextCartItems[item.id];
        delete nextCartItems[itemId];
        this.cartItems = nextCartItems;
      }

      const visibleItem = this.goods.find(goodsItem => String(goodsItem.id) === itemId);
      if (visibleItem) {
        visibleItem.quantity = nextQuantity;
      }
      this.syncCartState();
    },

    getAvatarUrl(avatar) {
      if (!avatar) return "";

      const url =
        avatar.startsWith("http://") || avatar.startsWith("https://")
          ? avatar
          : `/api${avatar}`;

      return `${url}${url.includes("?") ? "&" : "?"}v=${this.avatarVersion}`;
    },

    getResourceUrl(path) {
      if (!path) return "";
      if (path.startsWith("http://") || path.startsWith("https://")) {
        return path;
      }
      return `/api${path}`;
    },

    setActiveSlide(index) {
      this.activeSlideIndex = index;
      this.startSlideAutoplay();
    },

    startSlideAutoplay() {
      this.stopSlideAutoplay();

      if (typeof window === "undefined") {
        return;
      }

      if (!this.showSlidesSection || this.slides.length <= 1) {
        return;
      }

      this.slideTimer = window.setInterval(() => {
        this.activeSlideIndex = (this.activeSlideIndex + 1) % this.slides.length;
      }, this.slideAutoplayDelay);
    },

    stopSlideAutoplay() {
      if (this.slideTimer) {
        window.clearInterval(this.slideTimer);
        this.slideTimer = null;
      }
    },

    handleSlideClick(slide) {
      if (!slide.url) return;

      if (/^https?:\/\//i.test(slide.url)) {
        window.location.href = slide.url;
        return;
      }

      if (slide.url.startsWith("#/")) {
        this.$router.push(slide.url.replace(/^#/, "")).catch(() => {});
        return;
      }

      if (slide.url.startsWith("/")) {
        this.$router.push(slide.url).catch(() => {});
        return;
      }

      window.location.href = slide.url;
    },

    openCartDrawer() {
      this.showCartDrawer = true;
    },

    closeCartDrawer() {
      this.showCartDrawer = false;
    },

    increaseQuantity(item) {
      this.updateCartItem(item, Number(item.quantity || 0) + 1);
    },

    decreaseQuantity(item) {
      this.updateCartItem(item, Number(item.quantity || 0) - 1);
    },

    removeCartItem(item) {
      this.updateCartItem(item, 0);
    },

    clearCart() {
      this.goods.forEach(item => {
        item.quantity = 0;
      });
      this.cartItems = {};
      this.syncCartState();
      this.showClearConfirm = false;
    },

    buyNow(item) {
      setPaymentSummary({
        amount: item.price,
        count: 1,
        items: [
          {
            id: item.id,
            name: item.name,
            price: item.price,
            quantity: 1,
            image: item.image || ""
          }
        ],
        source: "buy-now"
      });
      this.$router.push({ name: "payment" });
    },

    goCheckout() {
      if (!this.totalCount) return;

      setPaymentSummary({
        amount: this.totalAmount,
        count: this.totalCount,
        items: this.selectedItems,
        source: "cart"
      });
      this.closeCartDrawer();
      this.$router.push({ name: "payment" });
    },

    formatPrice(value) {
      return Number(value || 0).toFixed(2);
    }
  }
};
</script>

<style scoped>
:root {
  color-scheme: light;
}

* {
  box-sizing: border-box;
}

html,
body,
#app,
.home-page {
  min-height: 100%;
}

body {
  margin: 0;
  font-family: "PingFang SC", "Microsoft YaHei", sans-serif;
  background:
    radial-gradient(circle at top, rgba(255, 255, 255, 0.9), transparent 38%),
    linear-gradient(180deg, #eef3ff 0%, #f6f8fc 46%, #eef2f7 100%);
  color: #1e293b;
}

button {
  font: inherit;
  touch-action: manipulation;
}

.home-page {
  --page-bg: linear-gradient(180deg, #f5f8ff 0%, #edf2f8 100%);
  --surface: rgba(255, 255, 255, 0.84);
  --text: #14213d;
  --accent-strong: #ff5a2a;
  display: flex;
  justify-content: center;
  align-items: stretch;
  height: 100vh;
  padding: 0;
  overflow: hidden;
  background:
    radial-gradient(circle at 12% 12%, rgba(87, 136, 255, 0.16), transparent 28%),
    radial-gradient(circle at 88% 0%, rgba(255, 140, 92, 0.14), transparent 26%),
    linear-gradient(180deg, #edf3ff 0%, #f4f6fb 52%, #edf1f7 100%);
}

.phone-shell {
  position: relative;
  width: min(100vw, 860px);
  height: 100vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: var(--page-bg);
  box-shadow:
    0 28px 60px rgba(15, 23, 42, 0.12),
    inset 0 1px 0 rgba(255, 255, 255, 0.65);
}

.phone-shell::before {
  content: "";
  position: absolute;
  inset: 0;
  background:
    radial-gradient(circle at top left, rgba(255, 255, 255, 0.75), transparent 30%),
    linear-gradient(180deg, rgba(255, 255, 255, 0.14), transparent 26%);
  pointer-events: none;
}

.top-bar,
.hero-stack,
.content-area,
.shop-summary {
  position: relative;
  z-index: 1;
}

.top-bar {
  order: 1;
  padding: 0 20px 12px;
}

.search-bar {
  display: flex;
  align-items: center;
  height: 56px;
  padding: 0 18px;
  border: 1px solid rgba(255, 255, 255, 0.58);
  border-radius: 24px;
  background: rgba(255, 255, 255, 0.72);
  box-shadow:
    0 12px 30px rgba(148, 163, 184, 0.12),
    inset 0 1px 0 rgba(255, 255, 255, 0.72);
  backdrop-filter: blur(18px);
}

.search-icon {
  width: 14px;
  height: 14px;
  border: 2px solid #9aa5b5;
  border-radius: 50%;
  position: relative;
  margin-right: 12px;
  flex: none;
}

.search-icon::after {
  content: "";
  position: absolute;
  width: 7px;
  height: 2px;
  right: -5px;
  bottom: -1px;
  background: #9aa5b5;
  border-radius: 999px;
  transform: rotate(45deg);
}

.search-input {
  flex: 1;
  min-width: 0;
  border: none;
  outline: none;
  background: transparent;
  color: #334155;
  font-size: 15px;
  letter-spacing: 0.02em;
}

.search-input::placeholder {
  color: #8f98a8;
}

.search-btn {
  height: 36px;
  padding: 0 16px;
  border: none;
  border-radius: 999px;
  background: linear-gradient(135deg, #2d7fff, #5b9cff);
  color: #fff;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 8px 18px rgba(45, 127, 255, 0.22);
}

.hero-stack {
  order: 0;
  display: grid;
  gap: 12px;
  padding: 18px 16px 14px;
  width: 100%;
  min-width: 0;
  max-width: 100%;
}

.hero-slider {
  position: relative;
  width: 100%;
  min-width: 0;
  max-width: 100%;
  overflow: hidden;
  border-radius: 30px;
  min-height: 178px;
  border: 1px solid rgba(255, 255, 255, 0.5);
  box-shadow:
    0 18px 34px rgba(88, 124, 183, 0.16),
    inset 0 1px 0 rgba(255, 255, 255, 0.88);
}

.slider-track {
  display: flex;
  height: 100%;
  transition: transform 0.45s ease;
}

.slide-card {
  position: relative;
  width: 100%;
  flex: 0 0 100%;
  min-width: 0;
  min-height: 178px;
  padding: 0;
  border: none;
  background: linear-gradient(135deg, #3264ff, #7bb7ff 60%, #ffc38c 100%);
  cursor: pointer;
  overflow: hidden;
}

.slide-card::after {
  content: "";
  position: absolute;
  inset: 0;
  background:
    linear-gradient(110deg, rgba(8, 15, 42, 0.1), rgba(8, 15, 42, 0.64)),
    linear-gradient(180deg, transparent 25%, rgba(15, 23, 42, 0.28));
}

.slide-image {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.slide-overlay {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  align-items: flex-start;
  gap: 10px;
  width: 100%;
  height: 100%;
  padding: 22px;
  color: #fff;
  text-align: left;
}

.slide-tag {
  display: inline-flex;
  align-items: center;
  height: 28px;
  padding: 0 12px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.16);
  backdrop-filter: blur(12px);
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.08em;
}

.slide-title {
  max-width: min(100%, 320px);
  font-size: 24px;
  font-weight: 800;
  line-height: 1.28;
  text-shadow: 0 10px 24px rgba(15, 23, 42, 0.3);
}

.slide-link {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.82);
}

.slider-dots {
  position: absolute;
  right: 16px;
  bottom: 16px;
  z-index: 2;
  display: flex;
  gap: 8px;
}

.slider-dot {
  width: 9px;
  height: 9px;
  padding: 0;
  border: none;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.42);
  cursor: pointer;
  transition: all 0.2s ease;
}

.slider-dot.active {
  width: 22px;
  border-radius: 999px;
  background: #fff;
}

.notice-banner {
  display: flex;
  width: 100%;
  min-width: 0;
  max-width: 100%;
  align-items: center;
  gap: 12px;
  min-height: 54px;
  padding: 0 16px;
  border: 1px solid rgba(255, 255, 255, 0.56);
  border-radius: 22px;
  background: rgba(255, 255, 255, 0.78);
  box-shadow:
    0 14px 24px rgba(148, 163, 184, 0.1),
    inset 0 1px 0 rgba(255, 255, 255, 0.78);
  backdrop-filter: blur(16px);
}

.notice-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 52px;
  height: 30px;
  padding: 0 12px;
  border-radius: 999px;
  background: linear-gradient(135deg, #ffb347, #ff7a18);
  color: #fff;
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.08em;
}

.notice-marquee {
  flex: 1;
  min-width: 0;
  max-width: 100%;
  overflow: hidden;
}

.notice-track {
  display: inline-flex;
  align-items: center;
  gap: 36px;
  min-width: 100%;
  width: max-content;
  white-space: nowrap;
  color: #526072;
  font-size: 13px;
  font-weight: 600;
  will-change: transform;
}

.notice-track.animated {
  animation-name: marquee-scroll;
  animation-timing-function: linear;
  animation-iteration-count: infinite;
}

.notice-text {
  display: inline-block;
}

.notice-text.clone {
  padding-right: 6px;
}

.content-area {
  order: 2;
  flex: 1;
  min-height: 0;
  display: grid;
  grid-template-columns: 120px minmax(0, 1fr);
  gap: 14px;
  padding: 0 16px 16px;
}

.category-list,
.goods-list {
  min-height: 0;
  overflow-y: auto;
  scrollbar-width: none;
}

.category-list::-webkit-scrollbar,
.goods-list::-webkit-scrollbar {
  display: none;
}

.category-list {
  padding: 14px 10px 122px;
  border: 1px solid rgba(255, 255, 255, 0.5);
  border-radius: 28px;
  background: rgba(255, 255, 255, 0.55);
  box-shadow:
    0 16px 28px rgba(148, 163, 184, 0.08),
    inset 0 1px 0 rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(20px);
}

.category-item {
  position: relative;
  width: 100%;
  padding: 14px 10px;
  border: none;
  border-radius: 18px;
  background: transparent;
  color: #7b8595;
  font-size: 13px;
  font-weight: 600;
  line-height: 1.45;
  cursor: pointer;
  text-align: center;
  transition: all 0.22s ease;
}

.category-item + .category-item {
  margin-top: 10px;
}

.category-item.active {
  color: var(--text);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.95), rgba(245, 248, 255, 0.9));
  box-shadow:
    0 14px 24px rgba(87, 136, 255, 0.12),
    inset 0 1px 0 rgba(255, 255, 255, 0.85);
}

.category-item.active::before {
  content: "";
  position: absolute;
  left: -10px;
  top: 50%;
  width: 4px;
  height: 24px;
  border-radius: 999px;
  background: linear-gradient(180deg, #2f8cff, #7ab2ff);
  transform: translateY(-50%);
}

.goods-panel {
  min-height: 0;
  display: flex;
  flex-direction: column;
}

.goods-list {
  padding: 2px 2px 122px;
}

.goods-card {
  display: grid;
  grid-template-columns: 86px minmax(0, 1fr) auto;
  align-items: center;
  gap: 16px;
  padding: 16px;
  margin-bottom: 14px;
  border: 1px solid rgba(255, 255, 255, 0.56);
  border-radius: 28px;
  background: var(--surface);
  box-shadow:
    0 18px 34px rgba(148, 163, 184, 0.14),
    inset 0 1px 0 rgba(255, 255, 255, 0.78);
  backdrop-filter: blur(16px);
}

.goods-card:last-child {
  margin-bottom: 0;
}

.goods-cover {
  width: 86px;
  height: 86px;
  border-radius: 24px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  background:
    linear-gradient(135deg, rgba(255, 255, 255, 0.78), rgba(234, 239, 247, 0.92)),
    linear-gradient(180deg, #f6f9fd, #edf3fa);
  color: #516074;
  font-size: 24px;
  font-weight: 700;
  box-shadow:
    inset 0 1px 0 rgba(255, 255, 255, 0.84),
    0 10px 18px rgba(148, 163, 184, 0.12);
}

.goods-cover-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.goods-main {
  min-width: 0;
}

.goods-main h3 {
  margin: 0 0 12px;
  color: var(--text);
  font-size: 16px;
  font-weight: 700;
  line-height: 1.45;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.goods-main strong {
  display: inline-flex;
  align-items: center;
  padding: 6px 12px;
  border-radius: 999px;
  background: rgba(255, 122, 24, 0.1);
  color: var(--accent-strong);
  font-size: 18px;
  font-weight: 800;
  letter-spacing: 0.01em;
}

.goods-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.goods-empty {
  display: flex;
  flex: 1;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 28px 20px 122px;
  border: 1px solid rgba(255, 255, 255, 0.56);
  border-radius: 28px;
  background: rgba(255, 255, 255, 0.66);
  color: #728096;
  text-align: center;
  box-shadow:
    0 18px 34px rgba(148, 163, 184, 0.12),
    inset 0 1px 0 rgba(255, 255, 255, 0.82);
}

.goods-empty strong {
  color: #1f2937;
  font-size: 16px;
}

.buy-btn,
.plus-btn,
.profile-btn,
.clear-btn,
.settle-btn,
.modal-btn,
.quantity-btn,
.drawer-close-btn,
.remove-item-btn,
.drawer-settle-btn {
  border: none;
  cursor: pointer;
}

.buy-btn:focus-visible,
.plus-btn:focus-visible,
.profile-btn:focus-visible,
.clear-btn:focus-visible,
.settle-btn:focus-visible,
.modal-btn:focus-visible,
.quantity-btn:focus-visible,
.drawer-close-btn:focus-visible,
.remove-item-btn:focus-visible,
.drawer-settle-btn:focus-visible,
.cart-total:focus-visible {
  outline: 3px solid rgba(47, 140, 255, 0.42);
  outline-offset: 2px;
}

.buy-btn {
  min-width: 62px;
  height: 36px;
  padding: 0 14px;
  border-radius: 18px;
  background: linear-gradient(135deg, #fdfefe, #eef6ff);
  color: #2f7ff1;
  font-size: 13px;
  font-weight: 700;
  box-shadow:
    inset 0 0 0 1px rgba(114, 171, 255, 0.8),
    0 8px 16px rgba(106, 157, 237, 0.14);
}

.plus-btn {
  min-width: 38px;
  width: 38px;
  height: 38px;
  padding: 0;
  border-radius: 19px;
  background: linear-gradient(135deg, #f8fbff, #e7f1ff);
  box-shadow:
    inset 0 0 0 1px rgba(200, 223, 255, 0.88),
    0 8px 16px rgba(118, 154, 206, 0.12);
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.plus-btn:hover,
.quantity-btn:hover {
  transform: translateY(-1px);
}

.quantity-control {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-height: 38px;
  padding: 3px;
  border-radius: 22px;
  background: rgba(231, 241, 255, 0.9);
  box-shadow: inset 0 0 0 1px rgba(200, 223, 255, 0.88);
}

.quantity-btn {
  width: 32px;
  height: 32px;
  padding: 0;
  border: none;
  border-radius: 50%;
  background: #fff;
  color: #2f7ff1;
  font-size: 19px;
  font-weight: 700;
  line-height: 1;
  cursor: pointer;
  transition: transform 0.2s ease, background 0.2s ease;
}

.quantity-btn:active {
  transform: scale(0.94);
}

.quantity-value {
  min-width: 22px;
  color: #1f4f98;
  font-size: 14px;
  font-weight: 800;
  line-height: 1;
  text-align: center;
}

.plus-sign {
  color: #6d8ebc;
  font-size: 22px;
  font-weight: 700;
  line-height: 1;
}

.shop-summary {
  order: 3;
  position: sticky;
  bottom: 0;
  z-index: 3;
  padding: 0 16px 16px;
  background: linear-gradient(180deg, rgba(245, 248, 255, 0) 0%, rgba(245, 248, 255, 0.86) 36%);
  backdrop-filter: blur(8px);
}

.shop-pill {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  width: 100%;
  padding: 12px 16px;
  border: 1px solid rgba(255, 255, 255, 0.6);
  border-radius: 24px 24px 18px 18px;
  background: rgba(255, 255, 255, 0.78);
  box-shadow:
    0 -6px 22px rgba(148, 163, 184, 0.08),
    inset 0 1px 0 rgba(255, 255, 255, 0.75);
  backdrop-filter: blur(18px);
}

.shop-profile {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
  flex: 1;
}

.shop-avatar-shell {
  width: 40px;
  height: 40px;
  flex-shrink: 0;
  overflow: hidden;
  border-radius: 50%;
  background: linear-gradient(135deg, #1f2937, #475569);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8px 16px rgba(15, 23, 42, 0.16);
}

.shop-avatar-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
  display: block;
}

.shop-avatar-fallback {
  display: flex;
  width: 100%;
  height: 100%;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 14px;
  font-weight: 700;
}

.shop-profile-meta {
  min-width: 0;
}

.shop-profile-label {
  display: block;
  margin-bottom: 2px;
  color: #8b95a7;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.18em;
}

.shop-profile-name {
  display: block;
  color: #162033;
  font-size: 15px;
  font-weight: 700;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.profile-btn {
  flex-shrink: 0;
  height: 34px;
  padding: 0 14px;
  border-radius: 999px;
  background: rgba(37, 99, 235, 0.08);
  color: #355ca8;
  font-size: 12px;
  font-weight: 700;
}

.cart-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  margin-top: 10px;
  padding: 14px 16px calc(14px + env(safe-area-inset-bottom, 0px));
  border: 1px solid rgba(255, 255, 255, 0.68);
  border-radius: 18px 18px 28px 28px;
  background: rgba(18, 23, 34, 0.94);
  box-shadow: 0 20px 34px rgba(15, 23, 42, 0.18);
}

.cart-total {
  display: flex;
  align-items: baseline;
  gap: 6px;
  min-width: 0;
  padding: 0;
  border: none;
  background: transparent;
  color: rgba(226, 232, 240, 0.72);
  font-size: 13px;
  font-weight: inherit;
  text-align: left;
  cursor: pointer;
  touch-action: manipulation;
}

.cart-total:hover strong {
  color: #ffffff;
}

.cart-total strong {
  font-size: 30px;
  color: #fff4df;
  line-height: 1;
  font-weight: 800;
}

.cart-total em {
  font-style: normal;
  font-size: 12px;
  color: rgba(226, 232, 240, 0.58);
}

.cart-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.clear-btn {
  color: rgba(241, 245, 249, 0.7);
  font-size: 13px;
  background: transparent;
}

.clear-btn:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

.settle-btn {
  height: 42px;
  padding: 0 22px;
  border-radius: 999px;
  background: linear-gradient(135deg, #ffb347, #ff7a18);
  color: #fff;
  font-size: 14px;
  font-weight: 800;
  box-shadow: 0 10px 18px rgba(255, 122, 24, 0.28);
}

.settle-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  box-shadow: none;
}

.cart-drawer-mask {
  position: fixed;
  inset: 0;
  z-index: 20;
  display: flex;
  justify-content: flex-end;
  background: rgba(15, 23, 42, 0.34);
  backdrop-filter: blur(8px);
}

.cart-drawer {
  display: flex;
  flex-direction: column;
  width: min(430px, 100vw);
  height: 100%;
  padding: 24px 20px calc(20px + env(safe-area-inset-bottom, 0px));
  border-left: 1px solid rgba(255, 255, 255, 0.72);
  background: rgba(248, 251, 255, 0.97);
  box-shadow: -18px 0 36px rgba(15, 23, 42, 0.16);
}

.cart-drawer-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding-bottom: 18px;
  border-bottom: 1px solid #e7edf6;
}

.cart-drawer-header h2 {
  margin: 0;
  color: #172238;
  font-size: 22px;
  line-height: 1.25;
}

.cart-drawer-header span {
  display: block;
  margin-top: 5px;
  color: #8490a3;
  font-size: 13px;
}

.drawer-close-btn {
  width: 40px;
  height: 40px;
  flex: 0 0 40px;
  padding: 0;
  border: none;
  border-radius: 50%;
  background: #eaf1fb;
  color: #4b5b72;
  font-size: 25px;
  line-height: 1;
  cursor: pointer;
}

.cart-drawer-list {
  min-height: 0;
  flex: 1;
  overflow-y: auto;
  padding: 16px 2px;
  scrollbar-width: none;
}

.cart-drawer-list::-webkit-scrollbar {
  display: none;
}

.cart-drawer-item {
  display: flex;
  gap: 12px;
  padding: 14px 0;
  border-bottom: 1px solid #e7edf6;
}

.drawer-item-cover {
  width: 68px;
  height: 68px;
  flex: 0 0 68px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  border-radius: 16px;
  background: linear-gradient(135deg, #edf4ff, #dce8f8);
  color: #52709e;
  font-size: 20px;
  font-weight: 800;
}

.drawer-item-cover img {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: cover;
}

.drawer-item-main {
  min-width: 0;
  flex: 1;
}

.drawer-item-main h3 {
  margin: 2px 0 8px;
  overflow: hidden;
  color: #1b2940;
  font-size: 15px;
  font-weight: 700;
  line-height: 1.4;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.drawer-item-meta,
.drawer-item-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.drawer-item-meta {
  color: #8490a3;
  font-size: 12px;
}

.drawer-item-meta strong {
  color: #ff6b2c;
  font-size: 15px;
  font-weight: 800;
}

.drawer-item-actions {
  justify-content: flex-start;
  margin-top: 10px;
}

.drawer-quantity-control {
  min-height: 34px;
  padding: 2px;
}

.drawer-quantity-control .quantity-btn {
  width: 30px;
  height: 30px;
}

.drawer-quantity-control .quantity-value {
  min-width: 20px;
}

.remove-item-btn {
  height: 34px;
  padding: 0 10px;
  border: none;
  border-radius: 17px;
  background: transparent;
  color: #9aa5b5;
  font-size: 12px;
  cursor: pointer;
}

.remove-item-btn:hover {
  color: #e45b48;
  background: #fff1ed;
}

.cart-drawer-empty {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: #8490a3;
  text-align: center;
}

.cart-drawer-empty strong {
  color: #334155;
  font-size: 17px;
}

.cart-drawer-empty span {
  font-size: 13px;
}

.cart-drawer-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  padding-top: 16px;
  border-top: 1px solid #e7edf6;
}

.drawer-total span {
  display: block;
  margin-bottom: 4px;
  color: #8490a3;
  font-size: 12px;
}

.drawer-total strong {
  color: #ff6b2c;
  font-size: 24px;
  font-weight: 800;
}

.drawer-settle-btn {
  min-width: 132px;
  height: 44px;
  padding: 0 18px;
  border: none;
  border-radius: 22px;
  background: linear-gradient(135deg, #ffb347, #ff7a18);
  color: #fff;
  font-size: 14px;
  font-weight: 800;
  cursor: pointer;
  box-shadow: 0 10px 18px rgba(255, 122, 24, 0.24);
}

.drawer-settle-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
  box-shadow: none;
}

.modal-mask {
  position: fixed;
  inset: 0;
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background: rgba(15, 23, 42, 0.34);
  backdrop-filter: blur(10px);
}

.confirm-modal {
  width: min(100%, 340px);
  padding: 24px;
  border: 1px solid rgba(255, 255, 255, 0.62);
  border-radius: 28px;
  background: rgba(255, 255, 255, 0.92);
  box-shadow: 0 24px 54px rgba(15, 23, 42, 0.18);
}

.confirm-modal h3 {
  margin: 0;
  color: #182234;
  font-size: 19px;
}

.confirm-modal p {
  margin: 12px 0 0;
  color: #667085;
  font-size: 13px;
  line-height: 1.8;
}

.confirm-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}

.modal-btn {
  height: 36px;
  padding: 0 16px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
}

.modal-btn.secondary {
  background: #eef2f7;
  color: #64748b;
}

.modal-btn.danger {
  background: linear-gradient(135deg, #ff8353, #ff5c3a);
  color: #fff;
}

@keyframes marquee-scroll {
  0% {
    transform: translate3d(0, 0, 0);
  }

  100% {
    transform: translate3d(calc(-50% - 18px), 0, 0);
  }
}

@media (max-width: 768px) {
  .phone-shell {
    width: 100%;
    height: 100vh;
    box-shadow: none;
  }

  .top-bar {
    padding: 0 12px 10px;
  }

  .search-bar {
    height: 48px;
    padding: 0 14px;
    border-radius: 22px;
  }

  .search-input {
    font-size: 13px;
  }

  .search-btn {
    height: 32px;
    padding: 0 12px;
    font-size: 12px;
  }

  .hero-stack {
    gap: 10px;
    padding: 12px 10px 10px;
  }

  .hero-slider,
  .slide-card {
    min-height: 148px;
    border-radius: 24px;
  }

  .slide-overlay {
    padding: 18px;
    gap: 8px;
  }

  .slide-title {
    max-width: 240px;
    font-size: 18px;
  }

  .slide-link,
  .notice-track {
    font-size: 12px;
  }

  .notice-banner {
    min-height: 48px;
    padding: 0 12px;
    border-radius: 18px;
  }

  .notice-badge {
    min-width: 46px;
    height: 26px;
    padding: 0 10px;
    font-size: 11px;
  }

  .content-area {
    grid-template-columns: 90px minmax(0, 1fr);
    gap: 10px;
    padding: 0 10px 10px;
  }

  .category-list {
    padding: 10px 8px 110px;
    border-radius: 24px;
  }

  .category-item {
    padding: 12px 6px;
    font-size: 12px;
  }

  .category-item + .category-item {
    margin-top: 8px;
  }

  .goods-list,
  .goods-empty {
    padding-bottom: 110px;
  }

  .goods-card {
    grid-template-columns: 62px minmax(0, 1fr) auto;
    gap: 10px;
    padding: 12px;
    margin-bottom: 10px;
    border-radius: 22px;
  }

  .goods-cover {
    width: 62px;
    height: 62px;
    border-radius: 18px;
    font-size: 17px;
  }

  .goods-main h3 {
    margin-bottom: 8px;
    font-size: 13px;
  }

  .goods-main strong {
    padding: 4px 10px;
    font-size: 14px;
  }

  .goods-actions {
    gap: 6px;
  }

  .quantity-control {
    gap: 4px;
    min-height: 34px;
    padding: 2px;
  }

  .quantity-btn {
    width: 29px;
    height: 29px;
    font-size: 17px;
  }

  .quantity-value {
    min-width: 19px;
    font-size: 12px;
  }

  .buy-btn {
    min-width: 46px;
    height: 30px;
    padding: 0 10px;
    font-size: 11px;
  }

  .plus-btn {
    min-width: 30px;
    width: 30px;
    height: 30px;
  }

  .plus-sign {
    font-size: 18px;
  }

  .shop-summary {
    padding: 0 10px 10px;
  }

  .shop-pill {
    padding: 10px 12px;
    border-radius: 20px 20px 16px 16px;
  }

  .shop-avatar-shell {
    width: 34px;
    height: 34px;
  }

  .shop-profile-label {
    font-size: 9px;
  }

  .shop-profile-name {
    font-size: 13px;
  }

  .profile-btn {
    height: 30px;
    padding: 0 12px;
    font-size: 10px;
  }

  .cart-bar {
    padding: 12px 12px calc(12px + env(safe-area-inset-bottom, 0px));
    border-radius: 16px 16px 24px 24px;
  }

  .cart-total {
    font-size: 10px;
  }

  .cart-total strong {
    font-size: 20px;
  }

  .clear-btn,
  .settle-btn,
  .cart-total em {
    font-size: 11px;
  }

  .settle-btn {
    height: 34px;
    padding: 0 16px;
  }

  .cart-drawer-mask {
    align-items: flex-end;
  }

  .cart-drawer {
    width: 100%;
    height: min(82vh, 680px);
    padding: 18px 16px calc(16px + env(safe-area-inset-bottom, 0px));
    border-top: 1px solid rgba(255, 255, 255, 0.72);
    border-right: none;
    border-left: none;
    border-radius: 24px 24px 0 0;
  }

  .cart-drawer-header {
    padding-bottom: 14px;
  }

  .cart-drawer-header h2 {
    font-size: 20px;
  }

  .cart-drawer-list {
    padding-top: 8px;
  }

  .cart-drawer-item {
    padding: 12px 0;
  }

  .drawer-item-cover {
    width: 60px;
    height: 60px;
    flex-basis: 60px;
  }

  .drawer-item-main h3 {
    font-size: 14px;
  }

  .drawer-settle-btn {
    min-width: 118px;
  }
}

@media (max-width: 420px) {
  .content-area {
    grid-template-columns: 78px minmax(0, 1fr);
    gap: 8px;
  }

  .category-list {
    padding-bottom: 106px;
  }

  .category-item {
    font-size: 11px;
  }

  .goods-list,
  .goods-empty {
    padding-bottom: 106px;
  }

  .goods-card {
    grid-template-columns: 54px minmax(0, 1fr) auto;
    padding: 10px;
  }

  .goods-cover {
    width: 54px;
    height: 54px;
    border-radius: 16px;
    font-size: 15px;
  }

  .goods-main h3 {
    font-size: 12px;
  }

  .goods-main strong {
    font-size: 13px;
  }

  .buy-btn {
    min-width: 40px;
    font-size: 10px;
  }

  .plus-btn {
    min-width: 28px;
    width: 28px;
    height: 28px;
  }

  .shop-profile {
    gap: 8px;
  }

  .profile-btn {
    padding: 0 10px;
    font-size: 9px;
  }

  .cart-actions {
    gap: 8px;
  }

  .settle-btn {
    padding: 0 14px;
    font-size: 10px;
  }

  .cart-drawer {
    padding-right: 12px;
    padding-left: 12px;
  }

  .drawer-item-cover {
    width: 54px;
    height: 54px;
    flex-basis: 54px;
    border-radius: 14px;
  }

  .drawer-item-meta {
    font-size: 11px;
  }

  .drawer-item-meta strong {
    font-size: 14px;
  }

  .drawer-settle-btn {
    min-width: 108px;
    height: 40px;
    padding: 0 14px;
    font-size: 13px;
  }
}

@media (min-width: 521px) {
  .home-page {
    padding: 0 24px;
  }

  .phone-shell {
    height: calc(100vh - 20px);
    margin: 10px 0;
    border-radius: 32px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .slider-track,
  .notice-track,
  .plus-btn,
  .quantity-btn,
  .cart-total strong {
    animation: none !important;
    transition: none !important;
  }
}
</style>
