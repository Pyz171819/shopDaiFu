<template>
  <div class="home-page">
    <section class="phone-shell">
      <header class="top-bar">
        <div class="search-bar">
          <span class="search-icon"></span>
          <span class="search-placeholder">搜索商品名称...</span>
          <button class="search-btn" type="button">搜索</button>
        </div>
      </header>

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

        <section class="goods-list">
          <article v-for="item in goods" :key="item.id" class="goods-card">
            <div class="goods-cover">
              <img
                v-if="item.image"
                :src="getGoodsImageUrl(item.image)"
                alt="商品图片"
                class="goods-cover-img"
              />
              <span v-else>{{ item.short }}</span>
            </div>
            <div class="goods-main">
              <h3>{{ item.name }}</h3>
              <strong>￥{{ formatPrice(item.price) }}</strong>
            </div>
            <div class="goods-actions">
              <button class="buy-btn" type="button" @click="buyNow(item)">购买</button>
              <button
                :class="['plus-btn', { selected: item.quantity > 0 }]"
                type="button"
                @click="toggleQuantity(item)"
              >
                <span v-if="item.quantity > 0" class="quantity-pill">{{ item.quantity }}</span>
                <span v-else class="plus-sign">+</span>
              </button>
            </div>
          </article>
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
              <strong class="shop-profile-name">{{ userProfile.nickname }}</strong>
            </div>
          </div>

          <button class="profile-btn" type="button" @click="$router.push({ name: 'profile' })">
            个人中心 &gt;
          </button>
        </div>

        <div class="cart-bar">
          <div class="cart-total">
            <span>合计</span>
            <strong>{{ formatPrice(totalAmount) }}</strong>
            <em>({{ totalCount }})</em>
          </div>
          <div class="cart-actions">
            <button class="clear-btn" type="button" @click="showClearConfirm = true">清空</button>
            <button class="settle-btn" type="button" :disabled="!totalCount" @click="goCheckout">
              去结算
            </button>
          </div>
        </div>
      </footer>

      <div v-if="showClearConfirm" class="modal-mask" @click.self="showClearConfirm = false">
        <div class="confirm-modal">
          <h3>确认清空商品？</h3>
          <p>当前已选中的商品数量和金额会被重置，确认后不可恢复。</p>
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
import { listCategory, listProductByCategory } from "../../api/home";

export default {
  name: "HomePage",
  data() {
    const cartState = getHomeCartState();

    return {
      userProfile: {},
      avatarVersion: Date.now(),
      activeCategoryId: null,
      showClearConfirm: false,
      categories: [],
      goods: [],
      cartItems: cartState.items,
      savedActiveCategoryId: cartState.activeCategoryId
    };
  },
  created() {
    this.fetchUserProfile();
    this.getCategoryList();
  },
  activated() {
    this.fetchUserProfile();
  },
  computed: {
    selectedItems() {
      return Object.values(this.cartItems)
          .filter(item => Number(item.quantity || 0) > 0)
          .map(item => ({
            id: item.id,
            name: item.name,
            price: Number(item.price || 0),
            quantity: Number(item.quantity || 0)
          }));
    },
    totalCount() {
      return this.selectedItems.reduce((sum, item) => sum + Number(item.quantity || 0), 0);
    },
    totalAmount() {
      return this.selectedItems.reduce((sum, item) => {
        return sum + Number(item.price || 0) * Number(item.quantity || 0);
      }, 0);
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

        // 每次重新获取用户信息后都刷新头像版本，避免浏览器继续显示旧图
        this.avatarVersion = Date.now();
      } catch (error) {
        this.userProfile = {};
      }
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

    async getGoodsList() {
      if (!this.activeCategoryId) {
        this.goods = [];
        return;
      }

      try {
        const res = await listProductByCategory(this.activeCategoryId);
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

    handleCategoryClick(category) {
      if (this.activeCategoryId === category.id) return;
      this.activeCategoryId = category.id;
      this.syncCartState();
      this.getGoodsList();
    },

    syncCartState() {
      setHomeCartState({
        activeCategoryId: this.activeCategoryId,
        items: this.cartItems
      });
    },

    updateCartItem(item) {
      if (item.quantity > 0) {
        this.cartItems = {
          ...this.cartItems,
          [item.id]: {
            id: item.id,
            name: item.name,
            price: Number(item.price || 0),
            quantity: Number(item.quantity || 0)
          }
        };
      } else {
        const nextCartItems = { ...this.cartItems };
        delete nextCartItems[item.id];
        this.cartItems = nextCartItems;
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

    getGoodsImageUrl(image) {
      if (!image) return "";
      if (image.startsWith("http://") || image.startsWith("https://")) {
        return image;
      }
      return `/api${image}`;
    },

    toggleQuantity(item) {
      if (item.quantity > 0) {
        item.quantity -= 1;
      } else {
        item.quantity = 1;
      }

      this.updateCartItem(item);
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
            quantity: 1
          }
        ]
      });
      this.$router.push({ name: "payment" });
    },

    goCheckout() {
      if (!this.totalCount) return;

      setPaymentSummary({
        amount: this.totalAmount,
        count: this.totalCount,
        items: this.selectedItems
      });
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

.top-bar {
  position: relative;
  z-index: 1;
  padding: 18px 20px 14px;
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

.search-placeholder {
  flex: 1;
  color: #8f98a8;
  font-size: 15px;
  letter-spacing: 0.02em;
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

.content-area {
  position: relative;
  z-index: 1;
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

.buy-btn,
.plus-btn,
.profile-btn,
.clear-btn,
.settle-btn,
.modal-btn {
  border: none;
  cursor: pointer;
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

.plus-btn.selected {
  width: auto;
  min-width: 50px;
  padding: 0 12px;
  background: linear-gradient(135deg, #53a1ff, #2f8cff);
  box-shadow: 0 12px 18px rgba(47, 140, 255, 0.28);
}

.plus-sign {
  color: #6d8ebc;
  font-size: 22px;
  font-weight: 700;
  line-height: 1;
}

.quantity-pill {
  color: #fff;
  font-size: 14px;
  font-weight: 700;
  line-height: 1;
}

.shop-summary {
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
  color: rgba(226, 232, 240, 0.72);
  font-size: 13px;
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

@media (max-width: 768px) {
  .phone-shell {
    width: 100%;
    height: 100vh;
    box-shadow: none;
  }

  .top-bar {
    padding: 12px 12px 10px;
  }

  .search-bar {
    height: 48px;
    padding: 0 14px;
    border-radius: 22px;
  }

  .search-placeholder {
    font-size: 13px;
  }

  .search-btn {
    height: 32px;
    padding: 0 12px;
    font-size: 12px;
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

  .goods-list {
    padding: 0 0 110px;
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

  .plus-btn.selected {
    min-width: 38px;
    padding: 0 8px;
  }

  .plus-sign {
    font-size: 18px;
  }

  .quantity-pill {
    font-size: 12px;
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

  .goods-list {
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

  .plus-btn.selected {
    min-width: 34px;
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
</style>
