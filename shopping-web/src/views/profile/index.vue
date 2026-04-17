
<template>
  <div class="profile-page">
    <section class="profile-shell">
      <!--      退出登录按钮-->
      <header class="hero-card">
        <button class="logout-btn" type="button" @click="showLogoutConfirm = true">退出登录</button>

        <!--      头部卡片-->
        <div class="hero-inner">
          <div class="hero-side">
            <div class="avatar-frame">
              <img
                v-if="userProfile.avatar"
                :src="getAvatarUrl(userProfile.avatar)"
                alt="头像"
                class="avatar-img"
              />
              <div v-else class="avatar-core">
                {{ userProfile.avatarText || "购" }}
              </div>
            </div>

            <div class="hero-badge">
              <span>店铺等级</span>
              <strong>PRO</strong>
            </div>
          </div>

          <div class="hero-copy">
            <span class="hero-eyebrow">ACCOUNT CENTER</span>
            <h1>{{ userProfile.nickname }}</h1>
            <p class="hero-id">账号 ID: {{ userProfile.userId }}</p>

            <div class="hero-tags">
              <span class="role-pill">普通用户</span>
              <span class="role-pill subtle">账户状态正常</span>
            </div>
          </div>
        </div>
      </header>

      <!--      流水、收入卡片-->
      <section class="stats-grid">
        <article class="stat-card">
          <span class="stat-label">今日流水</span>
          <strong class="stat-value">￥0.00</strong>
          <small>今日暂无新的入账记录</small>
        </article>

        <article class="stat-card accent">
          <span class="stat-label">账户余额</span>
          <strong class="stat-value">￥0.00</strong>
          <button class="settlement-btn" type="button">申请结算</button>
        </article>
      </section>


      <!--      四个菜单入口-->
      <nav class="action-tabs">
        <button
          v-for="tab in actionTabs"
          :key="tab"
          :class="['action-tab', { active: activeActionTab === tab }]"
          type="button"
          @click="activeActionTab = tab"
        >
          {{ tab }}
        </button>
      </nav>

      <!--      菜单一：我的订单-->
      <section v-if="activeActionTab === '我的订单'" class="content-panel">
        <div class="panel-head">
          <div>
            <span class="panel-eyebrow">ORDERS</span>
            <h2>订单列表</h2>
          </div>
        </div>

        <div class="order-filters">
          <button
            v-for="filter in statusFilters"
            :key="filter.value"
            :class="['filter-chip', { active: activeStatus === filter.value }]"
            type="button"
            @click="activeStatus = filter.value"
          >
            {{ filter.label }}
          </button>
        </div>

        <div v-if="ordersLoading" class="empty-block compact">
          <div class="empty-icon"></div>
          <h3>加载中...</h3>
          <p>正在获取订单列表</p>
        </div>

        <div v-else-if="filteredOrders.length === 0" class="empty-block compact">
          <div class="empty-icon"></div>
          <h3>暂无订单</h3>
          <p>{{ activeStatus === 'all' ? '还没有任何订单记录' : '该状态下暂无订单' }}</p>
        </div>

        <article v-else v-for="order in filteredOrders" :key="order.id" class="order-card">
          <div class="order-top">
            <div>
              <span class="order-caption">下单时间</span>
              <strong class="order-time">{{ order.time }}</strong>
            </div>
            <div class="order-state">
              <span :class="['state-text', getStatusClass(order.status)]">{{ statusTextMap[order.status] }}</span>
              <button class="trash-btn" type="button" aria-label="删除订单">删除</button>
            </div>
          </div>

          <div class="order-body">
            <div v-if="order.hasImage" class="order-cover-img">
              <img :src="order.cover" :alt="order.name">
            </div>
            <div v-else class="order-cover" :style="{ background: order.cover }">{{ order.short }}</div>
            <div class="order-info">
              <h3>{{ order.name }}</h3>
              <p>{{ order.desc }}</p>
              <strong>￥{{ formatPrice(order.amount) }}</strong>
            </div>
            <button
              v-if="order.status == 0"
              class="pay-btn"
              type="button"
              @click="payOrder(order)"
            >
              去支付
            </button>
            <span v-else class="paid-tag">已完成</span>
          </div>
        </article>
      </section>

      <!--      菜单二：商品管理-->
      <section v-else-if="activeActionTab === '商品管理'" class="content-panel">
        <div class="glass-panel goods-panel">
          <div class="panel-head">
            <div>
              <span class="panel-eyebrow">GOODS</span>
              <h2>我的商品</h2>
            </div>
            <button class="publish-btn" type="button" @click="openProductModal">+ 发布商品</button>
          </div>

          <div v-if="productsLoading" class="goods-empty-state">
            <div class="empty-icon cube"></div>
            <h3>商品列表加载中</h3>
            <p>正在获取你已发布的商品数据。</p>
          </div>

          <div v-else-if="myProducts.length" class="product-list">
            <article v-for="product in myProducts" :key="product.id" class="product-card">
              <div class="product-main">
                <div class="product-cover">
                  <img
                    v-if="product.image"
                    :src="getProductImageUrl(product.image)"
                    :alt="product.name"
                    class="product-cover-img"
                  />
                  <span v-else>{{ product.short }}</span>
                </div>

                <div class="product-info">
                  <div class="product-heading">
                    <h3>{{ product.name }}</h3>
                    <span :class="['product-status', product.statusClass]">
                      {{ product.statusText }}
                    </span>
                  </div>

                  <div class="product-meta">
                    <span class="product-category">{{ product.categoryName || "未分类" }}</span>
                    <span class="product-tag">{{ product.tagName || "默认标签" }}</span>
                  </div>

                  <strong class="product-price">￥{{ formatPrice(product.price) }}</strong>
                </div>
              </div>

            </article>
          </div>

          <div v-else class="goods-empty-state">
            <div class="empty-icon cube"></div>
            <h3>还没有商品</h3>
            <p>点击右上角“发布商品”即可新增，提交后会出现在这里。</p>
          </div>
        </div>
      </section>

      <!--      菜单三：结算记录-->
      <section v-else-if="activeActionTab === '结算记录'" class="content-panel">
        <div class="glass-panel">
          <div class="panel-head">
            <div>
              <span class="panel-eyebrow">SETTLEMENT</span>
              <h2>结算记录</h2>
            </div>
          </div>

          <div class="empty-block compact">
            <div class="empty-icon wallet"></div>
            <h3>暂无结算记录</h3>
            <p>暂时没有结算数据，如需处理可联系管理员。</p>
          </div>
        </div>
      </section>

      <!--      菜单四：修改资料-->
      <section v-else class="content-panel">
        <div class="glass-panel form-card">
          <div class="panel-head">
            <div>
              <span class="panel-eyebrow">PROFILE</span>
              <h2>修改资料</h2>
            </div>
          </div>

          <div class="form-field">
            <span>更换头像</span>
            <div class="upload-field">
              <ImageUpload v-model="profileForm.avatar" />
            </div>
          </div>

          <label class="form-field">
            <span>用户昵称</span>
            <input
                v-model.trim="profileForm.nickname"
                type="text"
                placeholder="请输入新的昵称"
            />
          </label>

          <button
              class="save-btn"
              type="button"
              @click="saveProfileInfo"
              :disabled="profileSaving"
          >
            {{ profileSaving ? "保存中..." : "保存修改" }}
          </button>

          <div class="form-divider"></div>

          <button class="logout-outline-btn" type="button" @click="showLogoutConfirm = true">
            退出登录
          </button>
          <button class="back-home-btn" type="button" @click="$router.push({ name: 'home' })">
            返回首页
          </button>
        </div>
      </section>

      <!--     返回首页按钮-->
      <button class="floating-home" type="button" @click="$router.push({ name: 'home' })">
        首页
      </button>

      <!--      菜单二：商品管理的发布商品弹窗-->
      <div v-if="showProductModal" class="modal-mask" @click.self="closeProductModal">
        <div class="confirm-modal product-modal">
          <div class="modal-top">
            <h3>发布新商品</h3>
            <button class="close-btn" type="button" @click="closeProductModal">×</button>
          </div>

          <label class="form-field">
            <span>商品名称</span>
            <input v-model.trim="productForm.name" type="text" placeholder="请输入商品名称" />
          </label>

          <label class="form-field">
            <span>商品价格</span>
            <input
              v-model.trim="productForm.price"
              type="number"
              min="0"
              step="0.01"
              placeholder="请输入商品价格"
            />
          </label>

          <label class="form-field">
            <span>商品分类</span>
            <select v-model="productForm.categoryId" class="select-field">
              <option value="" disabled>请选择分类</option>
              <option v-for="category in categories" :key="category.id" :value="category.id">
                {{ category.name }}
              </option>
            </select>
          </label>

          <label class="form-field">
            <span>店铺/标签名称</span>
            <input v-model.trim="productForm.shopName" type="text" placeholder="如：官方自营" />
          </label>

          <div class="form-field">
            <span>商品图片</span>
            <div class="upload-field">
              <ImageUpload v-model="productForm.image" />
            </div>
          </div>

          <div class="submit-tip">提交后需等待管理员审核通过才能显示。</div>

          <button class="submit-product-btn" type="button" @click="submitProduct" :disabled="productSubmitting">
            {{ productSubmitting ? "提交中..." : "提交审核" }}
          </button>
        </div>
      </div>

      <!--      退出登录按钮弹窗-->
      <div v-if="showLogoutConfirm" class="modal-mask" @click.self="showLogoutConfirm = false">
        <div class="confirm-modal">
          <h3>确认退出登录？</h3>
          <p>退出后将返回登录页，如需继续使用，需要重新登录。</p>
          <div class="confirm-actions">
            <button class="modal-btn secondary" type="button" @click="showLogoutConfirm = false">
              取消
            </button>
            <button class="modal-btn danger" type="button" @click="handleLogout">确认退出</button>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import ImageUpload from "@/components/ImageUpload.vue";
import { getUserSimpleInfo } from "../../api/auth";
import { listCategory } from "../../api/home";
import { createProduct, getMyProducts } from "../../api/products";
import { getMyOrders } from "../../api/order";
import { updateUser } from "../../api/user";
import { clearAuth, setPaymentSummary } from "../../utils/app-state";

export default {
  name: "ProfilePage",
  components: {
    ImageUpload
  },
  data() {
    return {
      userProfile: {},
      showLogoutConfirm: false,
      showProductModal: false,
      productsLoading: false,
      productSubmitting: false,
      profileSaving: false,
      avatarVersion: Date.now(),
      activeActionTab: "我的订单",
      activeStatus: "all",

      profileForm: {
        nickname: "",
        avatar: ""
      },

      productForm: {
        name: "",
        price: "",
        categoryId: "",
        image: "",
        shopName: ""
      },

      categories: [],
      myProducts: [],
      orders: [],
      ordersLoading: false,

      actionTabs: ["我的订单", "商品管理", "结算记录", "修改资料"],
      statusFilters: [
        { label: "全部", value: "all" },
        { label: "已支付", value: "paid" },
        { label: "待支付", value: "pending" }
      ],
      statusTextMap: {
        0: "待支付",
        1: "已支付",
        2: "已取消",
        3: "已退款"
      }
    };
  },
  computed: {
    // 不再需要前端筛选，直接显示从后端获取的订单
    filteredOrders() {
      return this.orders;
    }
  },
  watch: {
    // 监听筛选状态变化，重新查询订单
    activeStatus(newVal) {
      this.fetchMyOrders('noInit');
    },
    // 监听标签切换，点击"商品管理"时查询商品
    activeActionTab(newVal) {
      if (newVal === '商品管理') {
        this.fetchMyProducts();
      }
    }
  },
  created() {
    this.fetchUserProfile();
  },
  methods: {
    showMessage(type, message) {
      this.$message({
        ...(this.$messageDefaults || {}),
        type,
        message
      });
    },

    async fetchUserProfile() {
      try {
        const res = await getUserSimpleInfo();
        const profile = res.user;

        if (!profile || !(profile.userId || profile.id)) {
          throw new Error("获取用户信息失败");
        }

        const nickname = profile.nickName || profile.nickname || "";
        const userId = profile.userId ;
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

        this.profileForm.nickname = nickname;
        this.profileForm.avatar = avatar;
        this.fetchMyOrders(userId);

        this.avatarVersion = Date.now();
      } catch (error) {
        this.userProfile = {};
        this.profileForm.nickname = "";
        this.profileForm.avatar = "";
        this.showMessage("error", error.message || "获取用户信息失败");
      }
    },

    async fetchCategories() {
      try {
        const res = await listCategory();
        this.categories = (res.data || []).map(item => ({
          id: item.id,
          name: item.name
        }));
      } catch (error) {
        this.categories = [];
        this.showMessage("error", error.message || "获取分类失败");
      }
    },

    async fetchMyProducts() {
      this.productsLoading = true;
      try {
        const res = await getMyProducts();
        this.myProducts = (res.data || []).map(item => this.normalizeProduct(item));
      } catch (error) {
        this.myProducts = [];
        this.showMessage("error", error.message || "获取商品列表失败");
      } finally {
        this.productsLoading = false;
      }
    },

    async fetchMyOrders(userId) {

      this.ordersLoading = true;
      try {
        // 根据当前筛选状态传参
        let status = null;
        let orderCreateUserId = null;
        if (this.activeStatus === 'pending') {
          status = 0; // 待支付
        } else if (this.activeStatus === 'paid') {
          status = 1; // 已支付
        }
        // activeStatus === 'all' 时，status 为 null，查询全部
        
        // 获取当前用户ID
        if(userId === 'noInit'){
          orderCreateUserId = this.userProfile.userId || this.userProfile.id; 
        }else{
          orderCreateUserId = userId
        }
      

        const res = await getMyOrders(status, orderCreateUserId);
        this.orders = (res.rows || res.data || []).map(item => this.normalizeOrder(item));
      } catch (error) {
        this.orders = [];
        this.showMessage("error", error.message || "获取订单列表失败");
      } finally {
        this.ordersLoading = false;
      }
    },

    normalizeOrder(item) {
      // 解析商品列表
      let items = [];
      try {
        items = typeof item.items === 'string' ? JSON.parse(item.items) : (item.items || []);
      } catch (e) {
        items = [];
      }

      // 获取第一个商品信息
      const firstItem = items[0] || {};
      const name = item.orderName || firstItem.name || '订单';
      const short = name.substring(0, 1);
      
      // 获取商品图片
      let image = '';
      if (firstItem.image) {
        image = this.getProductImageUrl(firstItem.image);
      }

      return {
        id: item.id,
        outTradeNo: item.outTradeNo,
        time: item.createTime || item.create_time || '',
        name: name,
        desc: items.length > 1 ? `共${items.length}件商品` : (firstItem.shopName || ''),
        amount: Number(item.money || 0),
        short: short,
        status: Number(item.status), // 确保是数字类型
        cover: image || this.getOrderCover(short),
        items: items,
        hasImage: !!image
      };
    },

    getOrderCover(short) {
      const colors = [
        'linear-gradient(135deg, #ffbe7a, #f37d32)',
        'linear-gradient(135deg, #8fd3a8, #4aa36f)',
        'linear-gradient(135deg, #93c5fd, #3b82f6)',
        'linear-gradient(135deg, #fca5a5, #ef4444)',
        'linear-gradient(135deg, #c4b5fd, #8b5cf6)'
      ];
      const index = short.charCodeAt(0) % colors.length;
      return colors[index];
    },

    getStatusClass(status) {
      if (status === 0) return 'pending';
      if (status === 1) return 'paid';
      return 'other';
    },

    normalizeProduct(item) {
      const status = this.getProductStatus(
        item.status || item.auditStatus || item.reviewStatus || item.state
      );
      return {
        id: item.id,
        name: item.name || "未命名商品",
        price: Number(item.price || 0),
        image: item.image || "",
        short: item.name ? item.name.slice(0, 1) : "购",
        tagName: item.tagName || item.shopName || item.labelName || "默认标签",
        categoryName: item.categoryName || item.category || "未分类",
        statusText: status.text,
        statusClass: status.className
      };
    },

    getProductStatus(status) {
      if (status === 1 || status === "1" || status === "approved" || status === "passed") {
        return {
          text: "已通过",
          className: "approved"
        };
      }
      if (status === 2 || status === "2" || status === "rejected") {
        return {
          text: "未通过",
          className: "rejected"
        };
      }
      return {
        text: "审核中",
        className: "pending"
      };
    },

    openProductModal() {
      this.fetchCategories();
      this.resetProductForm();
      this.showProductModal = true;
    },

    closeProductModal() {
      this.showProductModal = false;
      this.resetProductForm();
    },

    resetProductForm() {
      this.productForm = {
        name: "",
        price: "",
        categoryId: "",
        image: "",
        shopName: ""
      };
    },

    async submitProduct() {
      if (!this.productForm.name) {
        this.showMessage("warning", "请输入商品名称");
        return;
      }
      if (!this.productForm.price && this.productForm.price !== 0) {
        this.showMessage("warning", "请输入商品价格");
        return;
      }
      if (!this.productForm.categoryId) {
        this.showMessage("warning", "请选择商品分类");
        return;
      }

      const payload = {
        name: this.productForm.name,
        price: this.productForm.price,
        categoryId: this.productForm.categoryId,
        shopName: this.productForm.shopName,
        image: this.productForm.image
      };

      this.productSubmitting = true;
      try {
        const res = await createProduct(payload);

        if (Number(res.code) !== 200) {
          this.showMessage("error", "您没有发布商品权限");
        } else {
          this.showMessage("success", "商品已提交审核");
        }
        this.closeProductModal();
        await this.fetchMyProducts();
      } catch (error) {
        this.showMessage("error", "商品提交失败");
      } finally {
        this.productSubmitting = false;
      }
    },

    getAvatarUrl(avatar) {
      if (!avatar) return "";

      const url =
          avatar.startsWith("http://") || avatar.startsWith("https://")
              ? avatar
              : `/api${avatar}`;

      return `${url}${url.includes("?") ? "&" : "?"}v=${this.avatarVersion}`;
    },

    getProductImageUrl(image) {
      if (!image) return "";
      if (image.startsWith("http://") || image.startsWith("https://")) {
        return image;
      }
      return `/api${image}`;
    },

    formatPrice(value) {
      return Number(value || 0).toFixed(2);
    },

    payOrder(order) {
      // 跳转到收银台页面
      this.$router.push({
        name: 'cashier',
        query: { outTradeNo: order.outTradeNo }
      });
    },

    async saveProfileInfo() {
      if (this.profileSaving) return;

      const nickname = (this.profileForm.nickname || "").trim();
      const avatar = this.profileForm.avatar || this.userProfile.avatar || "";
      const userId = this.userProfile.userId || this.userProfile.id;
      const userName = this.userProfile.userName || this.userProfile.username;

      if (!userId) {
        this.showMessage("error", "未获取到用户ID");
        return;
      }

      if (!userName) {
        this.showMessage("error", "未获取到用户名");
        return;
      }

      if (!nickname) {
        this.showMessage("warning", "请输入用户昵称");
        return;
      }

      const payload = {
        userId,
        userName,
        nickName: nickname,
        avatar
      };

      this.profileSaving = true;
      try {
        const res = await updateUser(payload);

        if (Number(res.code) !== 200) {
          throw new Error(res.msg || "资料修改失败");
        }

        await this.fetchUserProfile();
        this.showMessage("success", res.msg || "资料修改成功");
      } catch (error) {
        this.showMessage("error", error.message || "资料修改失败");
      } finally {
        this.profileSaving = false;
      }
    },

    handleLogout() {
      clearAuth();
      this.showLogoutConfirm = false;
      this.$router.push({ name: "login" });
    }
  }
};
</script>


<style scoped>
* {
  box-sizing: border-box;
}

.profile-page {
  min-height: 100vh;
  background:
    radial-gradient(circle at top left, rgba(85, 117, 255, 0.14), transparent 24%),
    radial-gradient(circle at 90% 10%, rgba(255, 173, 92, 0.12), transparent 18%),
    linear-gradient(180deg, #eef3fb 0%, #f6f8fc 44%, #eff3f7 100%);
}

.profile-shell {
  width: min(68vw, 1120px);
  min-height: 100vh;
  margin: 0 auto;
  padding: 28px 0 92px;
}

.upload-field {
  width: 100%;
  min-height: 128px;
  border: 1px solid rgba(203, 213, 225, 0.9);
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.92);
  display: flex;
  align-items: center;
  padding: 14px;
}

.upload-field :deep(.image-upload) {
  display: block;
}

.save-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.hero-card {
  position: relative;
  overflow: hidden;
  border-radius: 34px;
  background:
    radial-gradient(circle at top left, rgba(255, 255, 255, 0.18), transparent 28%),
    radial-gradient(circle at bottom right, rgba(255, 181, 107, 0.12), transparent 24%),
    linear-gradient(135deg, #111827 0%, #172554 55%, #0f172a 100%);
  box-shadow: 0 28px 64px rgba(15, 23, 42, 0.2);
}
.hero-card::before {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(120deg, rgba(255, 255, 255, 0.08), transparent 32%);
  pointer-events: none;
}

.hero-inner {
  position: relative;
  z-index: 1;
  min-height: 300px;
  padding: 34px;
  display: grid;
  grid-template-columns: 300px minmax(0, 1fr);
  gap: 12px;
  align-items: center;
  justify-content: center;
}

.hero-copy {
  min-width: 0;
  color: #fff;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
}

.hero-eyebrow,
.panel-eyebrow {
  display: inline-block;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.22em;
}

.hero-eyebrow {
  color: rgba(191, 219, 254, 0.82);
}

.hero-copy h1 {
  margin: 14px 0 0;
  font-size: clamp(34px, 4vw, 46px);
  line-height: 1.06;
  letter-spacing: 0.01em;
}

.hero-id {
  margin: 14px 0 0;
  color: rgba(226, 232, 240, 0.76);
  font-size: 15px;
}

.hero-tags {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 10px;
  margin-top: 18px;
}

.role-pill {
  display: inline-flex;
  align-items: center;
  min-height: 34px;
  padding: 0 14px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.12);
  color: #fff;
  font-size: 12px;
  font-weight: 700;
  backdrop-filter: blur(10px);
}

.role-pill.subtle {
  color: rgba(226, 232, 240, 0.88);
  background: rgba(255, 255, 255, 0.08);
}

.hero-side {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 16px;
  justify-self: end;
  transform: translateX(18px);
}

.avatar-frame {
  width: 142px;
  height: 142px;
  padding: 6px;
  border-radius: 50%;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.52), rgba(148, 163, 184, 0.14));
  box-shadow:
    0 18px 34px rgba(15, 23, 42, 0.18),
    inset 0 1px 0 rgba(255, 255, 255, 0.3);
}

.avatar-img,
.avatar-core {
  width: 100%;
  height: 100%;
  border-radius: 50%;
}

.avatar-img {
  object-fit: cover;
  display: block;
}

.avatar-core {
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #1d4ed8, #3b82f6);
  color: #fff;
  font-size: 40px;
  font-weight: 800;
}

.hero-badge {
  min-width: 136px;
  padding: 14px 18px;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.1);
  text-align: center;
  backdrop-filter: blur(12px);
}

.hero-badge span {
  display: block;
  color: rgba(226, 232, 240, 0.8);
  font-size: 12px;
}

.hero-badge strong {
  display: block;
  margin-top: 6px;
  color: #fff;
  font-size: 28px;
  letter-spacing: 0.08em;
}

.logout-btn {
  position: absolute;
  top: 18px;
  right: 18px;
  z-index: 2;
  height: 38px;
  padding: 0 16px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.08);
  color: #fff;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
  margin-top: 18px;
}

.stat-card {
  min-height: 148px;
  padding: 24px;
  border: 1px solid rgba(255, 255, 255, 0.64);
  border-radius: 28px;
  background: rgba(255, 255, 255, 0.84);
  box-shadow:
    0 18px 38px rgba(148, 163, 184, 0.12),
    inset 0 1px 0 rgba(255, 255, 255, 0.76);
  backdrop-filter: blur(16px);
}

.stat-card.accent {
  background:
    radial-gradient(circle at top right, rgba(255, 194, 116, 0.22), transparent 26%),
    rgba(255, 255, 255, 0.86);
}

.stat-label {
  display: block;
  color: #7c8798;
  font-size: 14px;
  font-weight: 600;
}

.stat-value {
  display: block;
  margin-top: 12px;
  color: #0f172a;
  font-size: clamp(34px, 4vw, 44px);
  line-height: 1;
}

.stat-card small {
  display: block;
  margin-top: 10px;
  color: #94a3b8;
  font-size: 12px;
}

.settlement-btn {
  margin-top: 14px;
  height: 38px;
  padding: 0 18px;
  border: none;
  border-radius: 999px;
  background: linear-gradient(135deg, #111827, #334155);
  color: #fff;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 10px 18px rgba(15, 23, 42, 0.16);
}

.action-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 18px;
}

.action-tab {
  min-height: 42px;
  padding: 0 18px;
  border: 1px solid rgba(255, 255, 255, 0.72);
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.72);
  color: #667085;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 8px 18px rgba(148, 163, 184, 0.08);
}

.action-tab.active {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: #fff;
  box-shadow: 0 14px 24px rgba(37, 99, 235, 0.22);
}

.content-panel {
  margin-top: 18px;
}

.glass-panel,
.order-card {
  border: 1px solid rgba(255, 255, 255, 0.64);
  border-radius: 30px;
  background: rgba(255, 255, 255, 0.84);
  box-shadow:
    0 20px 40px rgba(148, 163, 184, 0.12),
    inset 0 1px 0 rgba(255, 255, 255, 0.78);
  backdrop-filter: blur(16px);
}
.glass-panel {
  padding: 24px;
}

.panel-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 18px;
}

.panel-eyebrow {
  color: #7c8db5;
}

.panel-head h2 {
  margin: 8px 0 0;
  color: #0f172a;
  font-size: 28px;
}

.order-filters {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
  margin-bottom: 14px;
}

.filter-chip {
  height: 44px;
  border: 1px solid rgba(203, 213, 225, 0.82);
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.92);
  color: #64748b;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}

.filter-chip.active {
  background: linear-gradient(135deg, #111827, #334155);
  color: #fff;
  border-color: transparent;
}

.order-card {
  padding: 22px;
}

.order-card + .order-card {
  margin-top: 14px;
}

.order-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.order-caption {
  display: block;
  color: #94a3b8;
  font-size: 12px;
}

.order-time {
  display: block;
  margin-top: 6px;
  color: #0f172a;
  font-size: 15px;
}

.order-state {
  display: flex;
  align-items: center;
  gap: 10px;
}

.state-text {
  display: inline-flex;
  align-items: center;
  min-height: 30px;
  padding: 0 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
}

.state-text.pending {
  background: rgba(251, 191, 36, 0.14);
  color: #b45309;
}

.state-text.paid {
  background: rgba(34, 197, 94, 0.12);
  color: #15803d;
}

.trash-btn {
  border: none;
  background: transparent;
  color: #94a3b8;
  font-size: 12px;
  cursor: pointer;
}

.order-body {
  display: grid;
  grid-template-columns: 78px minmax(0, 1fr) auto;
  gap: 16px;
  align-items: center;
  margin-top: 18px;
}

.order-cover {
  width: 78px;
  height: 78px;
  border-radius: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 28px;
  font-weight: 800;
  flex-shrink: 0;
}

.order-cover-img {
  width: 78px;
  height: 78px;
  border-radius: 22px;
  overflow: hidden;
  flex-shrink: 0;
  background: #f5f7fa;
}

.order-cover-img img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.order-info {
  min-width: 0;
}

.order-info h3 {
  margin: 0;
  color: #0f172a;
  font-size: 20px;
}

.order-info p {
  margin: 8px 0 10px;
  color: #64748b;
  font-size: 13px;
}

.order-info strong {
  color: #f97316;
  font-size: 28px;
}

.goods-panel {
  position: relative;
}

.product-list {
  display: grid;
  gap: 12px;
}

.product-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  padding: 14px 16px;
  border-radius: 20px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(247, 250, 255, 0.9));
  box-shadow:
    inset 0 1px 0 rgba(255, 255, 255, 0.88),
    0 10px 20px rgba(148, 163, 184, 0.08);
}

.product-main {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 0;
  flex: 1;
}

.product-cover {
  width: 74px;
  height: 74px;
  border-radius: 18px;
  overflow: hidden;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #eef4ff, #ffffff);
  color: #2563eb;
  font-size: 22px;
  font-weight: 800;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9);
}

.product-cover-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.product-info {
  min-width: 0;
  flex: 1;
}

.product-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.product-heading h3 {
  margin: 0;
  color: #0f172a;
  font-size: 18px;
  line-height: 1.35;
}

.product-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 8px;
  flex-wrap: wrap;
}

.product-category,
.product-tag {
  display: inline-flex;
  align-items: center;
  min-height: 24px;
  padding: 0 9px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
}

.product-category {
  background: rgba(37, 99, 235, 0.08);
  color: #1d4ed8;
}

.product-tag {
  background: rgba(15, 23, 42, 0.08);
  color: #475569;
}

.product-price {
  display: block;
  margin-top: 10px;
  color: #ff4d4f;
  font-size: 24px;
  line-height: 1.1;
}

.product-status {
  display: inline-flex;
  align-items: center;
  min-height: 30px;
  padding: 0 12px;
  border-radius: 10px;
  font-size: 12px;
  font-weight: 700;
}

.product-status.approved {
  background: #28a745;
  color: #fff;
}

.product-status.pending {
  background: #f59e0b;
  color: #fff;
}
.product-status.rejected {
  background: #ef4444;
  color: #fff;
}

.goods-empty-state,
.empty-block {
  min-height: 260px;
  border: 1px dashed rgba(148, 163, 184, 0.34);
  border-radius: 24px;
  background: linear-gradient(180deg, rgba(248, 250, 252, 0.9), rgba(255, 255, 255, 0.78));
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 26px;
}

.goods-empty-state h3,
.empty-block h3 {
  margin: 0;
  color: #0f172a;
  font-size: 24px;
}

.goods-empty-state p,
.empty-block p {
  margin: 10px 0 0;
  max-width: 520px;
  color: #64748b;
  line-height: 1.7;
}

.empty-block.compact {
  min-height: 220px;
}

.empty-icon {
  width: 62px;
  height: 62px;
  border-radius: 20px;
  background: linear-gradient(135deg, #e8efff, #f6f8ff);
  position: relative;
  margin-bottom: 18px;
}

.empty-icon.cube::before,
.empty-icon.wallet::before,
.empty-icon.wallet::after {
  content: "";
  position: absolute;
  box-sizing: border-box;
}

.empty-icon.cube::before {
  inset: 15px;
  border: 2px solid #64748b;
  border-radius: 10px;
}

.empty-icon.wallet::before {
  left: 12px;
  right: 12px;
  top: 19px;
  bottom: 12px;
  border: 2px solid #64748b;
  border-radius: 8px;
}

.empty-icon.wallet::after {
  top: 12px;
  left: 18px;
  width: 22px;
  height: 8px;
  border: 2px solid #64748b;
  border-bottom: 0;
  border-radius: 8px 8px 0 0;
}

.form-field {
  display: block;
}

.form-field + .form-field {
  margin-top: 16px;
}

.form-field span {
  display: block;
  margin-bottom: 10px;
  color: #475569;
  font-size: 14px;
  font-weight: 700;
}

.file-field,
.upload-field,
.form-field input,
.select-field {
  width: 100%;
  min-height: 54px;
  border: 1px solid rgba(203, 213, 225, 0.9);
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.92);
}

.file-field,
.upload-field {
  display: flex;
  align-items: center;
  overflow: hidden;
}

.file-btn {
  width: 116px;
  min-height: 54px;
  border: none;
  background: linear-gradient(135deg, #eff6ff, #f8fbff);
  color: #1d4ed8;
  font-size: 13px;
  font-weight: 700;
  border-right: 1px solid rgba(203, 213, 225, 0.9);
  cursor: pointer;
}

.file-text {
  padding: 0 14px;
  color: #94a3b8;
  font-size: 14px;
}

.form-field input,
.select-field {
  padding: 0 16px;
  color: #0f172a;
  font-size: 14px;
  outline: none;
}

.select-field {
  appearance: none;
}

.hidden-input {
  display: none;
}

.save-btn,
.logout-outline-btn,
.back-home-btn,
.submit-product-btn,
.pay-btn,
.publish-btn,
.modal-btn,
.floating-home {
  border: none;
  cursor: pointer;
}

.save-btn {
  margin-top: 22px;
  width: 100%;
  min-height: 46px;
  border-radius: 999px;
  background: linear-gradient(135deg, #111827, #334155);
  color: #fff;
  font-size: 14px;
  font-weight: 800;
}

.pay-btn {
  height: 42px;
  padding: 0 18px;
  border-radius: 999px;
  background: linear-gradient(135deg, #ffb347, #ff7a18);
  color: #fff;
  font-size: 13px;
  font-weight: 800;
  box-shadow: 0 12px 22px rgba(249, 115, 22, 0.24);
}

.paid-tag {
  display: inline-flex;
  align-items: center;
  min-height: 34px;
  padding: 0 12px;
  border-radius: 999px;
  background: rgba(34, 197, 94, 0.12);
  color: #15803d;
  font-size: 12px;
  font-weight: 700;
}

.publish-btn {
  height: 40px;
  padding: 0 18px;
  border-radius: 999px;
  background: linear-gradient(135deg, #2563eb, #3b82f6);
  color: #fff;
  font-size: 13px;
  font-weight: 700;
  box-shadow: 0 12px 22px rgba(37, 99, 235, 0.22);
}

.form-divider {
  height: 1px;
  margin: 22px 0;
  background: rgba(203, 213, 225, 0.6);
}

.logout-outline-btn,
.back-home-btn {
  width: 100%;
  min-height: 46px;
  border-radius: 999px;
  font-size: 14px;
  font-weight: 800;
}

.logout-outline-btn {
  border: 1px solid rgba(248, 113, 113, 0.4);
  background: rgba(255, 255, 255, 0.84);
  color: #dc2626;
}
.back-home-btn {
  margin-top: 10px;
  background: transparent;
  color: #64748b;
}

.floating-home {
  position: fixed;
  right: 24px;
  bottom: 24px;
  min-width: 54px;
  height: 54px;
  padding: 0 16px;
  border-radius: 999px;
  background: rgba(15, 23, 42, 0.92);
  color: #fff;
  font-size: 13px;
  font-weight: 700;
  box-shadow: 0 16px 30px rgba(15, 23, 42, 0.2);
}

.modal-mask {
  position: fixed;
  inset: 0;
  z-index: 30;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background: rgba(15, 23, 42, 0.38);
  backdrop-filter: blur(10px);
}

.confirm-modal {
  width: min(100%, 360px);
  padding: 24px;
  border: 1px solid rgba(255, 255, 255, 0.68);
  border-radius: 28px;
  background: rgba(255, 255, 255, 0.92);
  box-shadow: 0 26px 54px rgba(15, 23, 42, 0.18);
}

.product-modal {
  width: min(100%, 560px);
  padding: 0;
  overflow-x: hidden;
  overflow-y: auto;
  max-height: min(88vh, 760px);
  display: flex;
  flex-direction: column;
}

.modal-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 22px 24px;
  border-bottom: 1px solid rgba(226, 232, 240, 0.9);
}

.modal-top h3,
.confirm-modal h3 {
  margin: 0;
  color: #0f172a;
  font-size: 20px;
}

.product-modal .form-field,
.product-modal .submit-tip,
.product-modal .submit-product-btn {
  margin-left: 24px;
  margin-right: 24px;
}

.product-modal .form-field {
  flex-shrink: 0;
}

.product-modal .form-field:first-of-type {
  margin-top: 20px;
}

.close-btn {
  border: none;
  background: transparent;
  color: #94a3b8;
  font-size: 34px;
  line-height: 1;
  cursor: pointer;
}

.submit-tip {
  margin-top: 18px;
  padding: 16px 18px;
  border-radius: 14px;
  background: linear-gradient(135deg, #d9f2ff, #c4ebff);
  color: #2563eb;
  font-size: 14px;
}

.submit-product-btn {
  width: calc(100% - 48px);
  min-height: 48px;
  margin-top: 20px;
  margin-bottom: 24px;
  border-radius: 999px;
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: #fff;
  font-size: 18px;
  font-weight: 800;
}

.submit-product-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.confirm-modal p {
  margin: 12px 0 0;
  color: #64748b;
  font-size: 14px;
  line-height: 1.7;
}

.confirm-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 22px;
}

.modal-btn {
  height: 38px;
  padding: 0 16px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 700;
}

.modal-btn.secondary {
  background: #eef2f7;
  color: #475569;
}

.modal-btn.danger {
  background: linear-gradient(135deg, #111827, #334155);
  color: #fff;
}

@media (max-width: 1280px) {
  .profile-shell {
    width: min(76vw, 1040px);
  }
}

@media (max-width: 980px) {
  .profile-shell {
    width: min(88vw, 920px);
    padding-top: 20px;
  }

  .hero-inner {
    grid-template-columns: 1fr;
    justify-items: center;
  }

  .hero-side {
    flex-direction: column;
    justify-content: center;
    justify-self: center;
    transform: none;
  }

  .product-card {
    flex-direction: column;
    align-items: stretch;
    padding: 14px;
  }

}

@media (max-width: 768px) {
  .profile-shell {
    width: 100%;
    padding: 0 0 92px;
  }

  .hero-card {
    border-radius: 0 0 28px 28px;
    box-shadow: none;
  }

  .hero-inner {
    min-height: 0;
    padding: 28px 16px 22px;
    gap: 20px;
  }

  .hero-copy h1 {
    font-size: 28px;
  }

  .hero-id {
    font-size: 13px;
  }

  .hero-side {
    flex-direction: column;
    align-items: center;
    justify-self: center;
    transform: none;
  }

  .avatar-frame {
    width: 102px;
    height: 102px;
  }

  .avatar-core {
    font-size: 30px;
  }

  .logout-btn {
    top: 12px;
    right: 12px;
    height: 34px;
    padding: 0 12px;
    font-size: 11px;
  }

  .stats-grid,
  .action-tabs,
  .content-panel {
    padding-left: 12px;
    padding-right: 12px;
  }

  .stats-grid {
    gap: 10px;
    margin-top: 12px;
  }

  .stat-card {
    min-height: 116px;
    padding: 18px;
    border-radius: 22px;
  }

  .action-tabs {
    gap: 8px;
    margin-top: 14px;
  }

  .action-tab {
    min-height: 36px;
    padding: 0 14px;
    font-size: 12px;
  }

  .glass-panel,
  .order-card {
    border-radius: 22px;
  }

  .glass-panel {
    padding: 16px;
  }

  .panel-head h2 {
    font-size: 22px;
  }

  .order-body {
    grid-template-columns: 58px minmax(0, 1fr);
  }

  .order-cover {
    width: 58px;
    height: 58px;
    border-radius: 18px;
    font-size: 22px;
  }

  .order-info h3 {
    font-size: 16px;
  }

  .order-info p {
    font-size: 12px;
  }

  .order-info strong {
    font-size: 20px;
  }

  .pay-btn,
  .paid-tag {
    grid-column: 1 / -1;
    justify-self: start;
    margin-top: 6px;
  }

  .product-main {
    align-items: flex-start;
  }

  .product-cover {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    font-size: 20px;
  }

  .product-heading {
    align-items: flex-start;
    flex-direction: column;
  }

  .product-heading h3 {
    font-size: 16px;
  }

  .product-price {
    font-size: 22px;
  }

  .goods-empty-state,
  .empty-block {
    min-height: 190px;
    border-radius: 18px;
    padding: 20px 16px;
  }

  .goods-empty-state h3,
  .empty-block h3 {
    font-size: 18px;
  }

  .goods-empty-state p,
  .empty-block p {
    font-size: 13px;
  }

  .file-field,
  .upload-field,
  .form-field input,
  .select-field,
  .file-btn {
    min-height: 48px;
  }

  .save-btn,
  .logout-outline-btn,
  .back-home-btn {
    min-height: 42px;
    font-size: 13px;
  }

  .submit-product-btn {
    font-size: 16px;
  }

  .product-modal {
    width: min(100%, 520px);
    max-height: calc(100vh - 24px);
  }

  .product-modal .form-field,
  .product-modal .submit-tip,
  .product-modal .submit-product-btn {
    margin-left: 18px;
    margin-right: 18px;
  }

  .modal-top {
    padding: 18px 18px 16px;
  }

  .product-modal .form-field:first-of-type {
    margin-top: 16px;
  }

  .submit-product-btn {
    width: calc(100% - 36px);
    margin-bottom: 18px;
  }

  .floating-home {
    right: 14px;
    bottom: 14px;
    min-width: 48px;
    height: 48px;
    padding: 0 14px;
    font-size: 12px;
  }
}

@media (max-width: 420px) {
  .hero-copy h1 {
    font-size: 24px;
  }

  .hero-tags {
    gap: 8px;
  }

  .action-tabs {
    gap: 6px;
  }

  .action-tab {
    padding: 0 12px;
    font-size: 11px;
  }

  .panel-head {
    flex-direction: column;
    align-items: stretch;
  }

  .publish-btn {
    width: 100%;
  }

  .order-filters {
    grid-template-columns: 1fr;
  }

  .product-main {
    flex-direction: column;
    gap: 12px;
  }

  .modal-mask {
    align-items: flex-end;
    padding: 8px;
  }

  .product-modal {
    width: 100%;
    max-height: calc(100vh - 16px);
    border-radius: 24px 24px 18px 18px;
  }

  .product-modal .form-field,
  .product-modal .submit-tip,
  .product-modal .submit-product-btn {
    margin-left: 14px;
    margin-right: 14px;
  }

  .modal-top {
    padding: 16px 14px 14px;
  }

  .modal-top h3,
  .confirm-modal h3 {
    font-size: 18px;
  }

  .product-modal .form-field:first-of-type {
    margin-top: 14px;
  }

  .form-field + .form-field {
    margin-top: 12px;
  }

  .form-field span {
    margin-bottom: 8px;
    font-size: 13px;
  }

  .file-field,
  .upload-field,
  .form-field input,
  .select-field,
  .file-btn {
    min-height: 44px;
    border-radius: 14px;
  }

  .submit-tip {
    margin-top: 14px;
    padding: 12px 14px;
    font-size: 12px;
    line-height: 1.6;
  }

  .submit-product-btn {
    width: calc(100% - 28px);
    min-height: 44px;
    margin-top: 16px;
    margin-bottom: 14px;
    font-size: 15px;
  }
}
</style>
