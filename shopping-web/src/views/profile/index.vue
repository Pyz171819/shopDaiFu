
<template>
  <div class="profile-page">
    <section class="profile-shell">
      <!--      头部卡片-->
      <header class="hero-card">
        <button class="logout-btn" type="button" @click="showLogoutConfirm = true">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>
          </svg>
          退出
        </button>

        <!--      头部卡片-->
        <div class="hero-inner">
          <div class="hero-main">
            <div class="avatar-section">
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
              <div class="level-badge">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor">
                  <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
                <span>VIP</span>
              </div>
            </div>

            <div class="hero-content">
              <div class="user-name-section">
                <h1>{{ userProfile.nickname }}</h1>
                <div class="user-badges">
                  <span class="badge-item verified">
                    <svg viewBox="0 0 24 24" width="12" height="12" fill="currentColor">
                      <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    已认证
                  </span>
                </div>
              </div>
              <p class="user-id">ID: {{ userProfile.userId }}</p>
            </div>
          </div>
        </div>
      </header>

      <!--      流水、收入卡片-->
      <section class="stats-grid">
        <article class="stat-card">
          <span class="stat-label">今日流水</span>
          <strong class="stat-value">￥{{ formatPrice(todayRevenue) }}</strong>
          <small>{{ todayRevenue > 0 ? '今日已有新的入账记录' : '今日暂无新的入账记录' }}</small>
        </article>

        <article class="stat-card accent">
          <span class="stat-label">账户余额</span>
          <strong class="stat-value">￥{{ formatPrice(accountBalance) }}</strong>
          <button class="settlement-btn" type="button" @click="openSettlementModal">申请结算</button>
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
              <button class="trash-btn" type="button" aria-label="删除订单" @click="confirmDeleteOrder(order)">删除</button>
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
              <strong>{{ formatPrice(order.amount) }}</strong>
            </div>

            <div class="order-action">
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

          <div v-if="settlementsLoading" class="empty-block compact">
            <div class="empty-icon wallet"></div>
            <h3>加载中...</h3>
            <p>正在获取结算记录</p>
          </div>

          <div v-else-if="settlements.length === 0" class="empty-block compact">
            <div class="empty-icon wallet"></div>
            <h3>暂无结算记录</h3>
            <p>暂时没有结算数据，如需处理可联系管理员。</p>
          </div>

          <div v-else class="settlement-list">
            <article v-for="settlement in settlements" :key="settlement.id" class="settlement-card">
              <div class="settlement-header">
                <div>
                  <span class="settlement-label">申请时间</span>
                  <strong class="settlement-time">{{ settlement.createTime }}</strong>
                </div>
                <span :class="['settlement-status', getSettlementStatusClass(settlement.status)]">
                  {{ settlement.statusText }}
                </span>
              </div>

              <div class="settlement-body">
                <div class="settlement-amount-section">
                  <span class="settlement-amount-label">结算金额</span>
                  <strong class="settlement-amount">¥{{ formatPrice(settlement.amount) }}</strong>
                </div>


                <div v-if="settlement.remark && Number(settlement.status) === 2" class="settlement-remark rejected">
                  <span class="remark-label">拒绝原因</span>
                  <p class="remark-text">{{ settlement.remark }}</p>
                </div>

                <div v-else-if="settlement.remark" class="settlement-remark">
                  <span class="remark-label">备注</span>
                  <p class="remark-text">{{ settlement.remark }}</p>
                </div>
              </div>
            </article>
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

      <!--      申请结算弹窗-->
      <div v-if="showSettlementModal" class="modal-mask" @click.self="closeSettlementModal">
        <div class="confirm-modal settlement-modal">
          <div class="modal-top">
            <h3>申请结算</h3>
            <button class="close-btn" type="button" @click="closeSettlementModal">×</button>
          </div>

          <div class="balance-display">
            <span class="balance-label">当前余额:</span>
            <span class="balance-value">¥{{ userProfile.bizUser.balance }}</span>
          </div>

          <label class="form-field">
            <span>结算金额</span>
            <input
              v-model.trim="settlementForm.money"
              type="number"
              min="0"
              placeholder="请输入金额"
            />
          </label>

          <div class="form-field">
            <span>上传凭证</span>
            <div class="upload-field">
              <ImageUpload v-model="settlementForm.qrcode" />
            </div>
            <small style="color: #94a3b8; font-size: 12px; margin-top: 8px; display: block;">
              请上传收款凭证
            </small>
          </div>

          <div class="settlement-actions">
            <button class="modal-btn secondary" type="button" @click="closeSettlementModal">
              取消
            </button>
            <button
              class="modal-btn primary"
              type="button"
              @click="submitSettlement"
              :disabled="settlementSubmitting"
            >
              {{ settlementSubmitting ? "提交中..." : "确认申请" }}
            </button>
          </div>
        </div>
      </div>

      <!--      删除订单确认弹窗-->
      <div v-if="showDeleteOrderConfirm" class="modal-mask" @click.self="cancelDeleteOrder">
        <div class="confirm-modal">
          <h3>确认删除订单？</h3>
          <p v-if="orderToDelete">确定要删除订单"{{ orderToDelete.name }}"吗？删除后将无法恢复。</p>
          <div class="confirm-actions">
            <button class="modal-btn secondary" type="button" @click="cancelDeleteOrder">
              取消
            </button>
            <button 
              class="modal-btn danger" 
              type="button" 
              @click="handleDeleteOrder"
              :disabled="orderDeleting"
            >
              {{ orderDeleting ? "删除中..." : "确认删除" }}
            </button>
          </div>
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
import { getMyOrders, getTodayRevenue, deleteOrder } from "../../api/order";
import { updateUser } from "../../api/user";
import { applySettlement, getSettlementList } from "../../api/settlement";
import { clearAuth } from "../../utils/app-state";

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
      showSettlementModal: false,
      showDeleteOrderConfirm: false,
      orderToDelete: null,
      productsLoading: false,
      productSubmitting: false,
      profileSaving: false,
      settlementSubmitting: false,
      orderDeleting: false,
      avatarVersion: Date.now(),
      activeActionTab: "我的订单",
      activeStatus: "all",
      accountBalance: 0,
      todayRevenue: 0,

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

      settlementForm: {
        amount: "",
        certificate: ""
      },

      categories: [],
      myProducts: [],
      orders: [],
      ordersLoading: false,
      settlements: [],
      settlementsLoading: false,

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
      } else if (newVal === '结算记录') {
        this.fetchSettlements();
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
        const accountBalance = profile.bizUser.balance ;
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
        this.accountBalance = accountBalance
        this.fetchMyOrders(userId);
        this.fetchTodayRevenue(userId);

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

    async fetchTodayRevenue(userId) {
      try {
        if (!userId) {
          return;
        }
        
        const res = await getTodayRevenue(userId);
        if (res.code === 200) {
          // 后端返回BigDecimal，可能在data字段中
          this.todayRevenue = Number(res.data || 0);
        }
      } catch (error) {
        // 静默失败，使用默认值0
        console.error('获取今日流水失败:', error);
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

    confirmDeleteOrder(order) {
      this.orderToDelete = order;
      this.showDeleteOrderConfirm = true;
    },

    cancelDeleteOrder() {
      this.orderToDelete = null;
      this.showDeleteOrderConfirm = false;
    },

    async handleDeleteOrder() {
      if (!this.orderToDelete || this.orderDeleting) return;

      this.orderDeleting = true;
      try {
        const res = await deleteOrder(this.orderToDelete.outTradeNo);
        
        if (Number(res.code) !== 200) {
          throw new Error(res.msg || "删除订单失败");
        }
        
        this.showMessage("success", res.msg || "订单已删除");
        this.cancelDeleteOrder();
        
        // 刷新订单列表
        const userId = this.userProfile.userId;
        if (userId) {
          await this.fetchMyOrders(userId);
        }
      } catch (error) {
        this.showMessage("error", error.message || "删除订单失败");
      } finally {
        this.orderDeleting = false;
      }
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

    async fetchSettlements() {
      this.settlementsLoading = true;
      try {
        const userId = this.userProfile.userId;
        if (!userId) {
          throw new Error('用户ID不存在');
        }
        
        const res = await getSettlementList(userId);
        this.settlements = (res.rows || res.data || []).map(item => this.normalizeSettlement(item));
        console.log('res：',res);
        console.log('this.settlements：',this.settlements);
        
      } catch (error) {
        this.settlements = [];
        this.showMessage("error", error.message || "获取结算记录失败");
      } finally {
        this.settlementsLoading = false;
      }
    },

    normalizeSettlement(item) {
      return {
        id: item.id,
        amount: Number(item.money || 0),
        createTime: item.createTime || '',
        auditTime: item.auditTime || '',
        payTime: item.payTime || '',
        status: Number(item.status || 0),
        statusText: this.getSettlementStatusText(item.status),
        certificate: item.qrcode || '',
        remark: item.reason || item.remark || '',
        withdrawNo: item.withdrawNo || '',
        nickName: item.nickName || ''
      };
    },

    getSettlementStatusText(status) {
      const statusMap = {
        '0': '审核中',
        '1': '已通过',
        '2': '已拒绝',
        0: '审核中',
        1: '已通过',
        2: '已拒绝'
      };
      return statusMap[status] || '未知';
    },

    openSettlementModal() {
      this.resetSettlementForm();
      this.showSettlementModal = true;
    },

    closeSettlementModal() {
      this.showSettlementModal = false;
      this.resetSettlementForm();
    },

    resetSettlementForm() {
      this.settlementForm = {
        amount: "",
        certificate: ""
      };
    },

    async submitSettlement() {
      if (this.settlementSubmitting) return;
      const userId = this.userProfile.userId;
      const amount = this.userProfile.bizUser.balance;
      
      if (!this.settlementForm.money || isNaN(amount) || amount <= 0) {
        this.showMessage("warning", "请输入有效的结算金额");
        return;
      }

      if (amount < this.settlementForm.money) {
        this.showMessage("warning", "结算金额不能大于账户余额");
        return;
      }

      if (!this.settlementForm.qrcode) {
        this.showMessage("warning", "请上传收款凭证");
        return;
      }

      const payload = {
        userId: userId,
        money:  this.settlementForm.money,
        qrcode: this.settlementForm.qrcode
      };

      this.settlementSubmitting = true;
      try {
        const res = await applySettlement(payload);
        
        if (Number(res.code) !== 200) {
          throw new Error(res.msg || "结算申请提交失败");
        }
        
        this.showMessage("success", "结算申请已提交，请等待审核");
        this.fetchUserProfile();
        this.closeSettlementModal();

      } catch (error) {
        this.showMessage("error", error.message || "结算申请提交失败");
      } finally {
        this.settlementSubmitting = false;
      }
    },

    getSettlementStatusClass(status) {
      // 转换为数字进行比较
      const numStatus = Number(status);
      if (numStatus === 0) return 'pending';
      if (numStatus === 1) return 'approved';
      if (numStatus === 2) return 'rejected';
      return 'unknown';
    },

    getImageUrl(image) {
      if (!image) return "";
      if (image.startsWith("http://") || image.startsWith("https://")) {
        return image;
      }
      return `/api${image}`;
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
  border-radius: 24px;
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
  box-shadow: 0 20px 40px rgba(15, 23, 42, 0.3);
}

.hero-card::before {
  content: "";
  position: absolute;
  top: -50%;
  right: -20%;
  width: 300px;
  height: 300px;
  background: radial-gradient(circle, rgba(59, 130, 246, 0.15), transparent 70%);
  pointer-events: none;
}

.hero-inner {
  position: relative;
  z-index: 1;
  padding: 32px 24px;
}

.hero-main {
  display: flex;
  align-items: center;
  gap: 20px;
}

.avatar-section {
  position: relative;
  flex-shrink: 0;
}

.avatar-frame {
  width: 80px;
  height: 80px;
  padding: 4px;
  border-radius: 50%;
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.4), rgba(147, 197, 253, 0.2));
  box-shadow: 0 8px 24px rgba(59, 130, 246, 0.3);
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
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  color: #fff;
  font-size: 32px;
  font-weight: 800;
}

.level-badge {
  position: absolute;
  bottom: -4px;
  right: -4px;
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 4px 10px;
  border-radius: 12px;
  background: linear-gradient(135deg, #fbbf24, #f59e0b);
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  box-shadow: 0 4px 12px rgba(251, 191, 36, 0.4);
}

.level-badge svg {
  flex-shrink: 0;
}

.hero-content {
  flex: 1;
  min-width: 0;
}

.user-name-section {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.user-name-section h1 {
  margin: 0;
  color: #fff;
  font-size: 24px;
  font-weight: 700;
  line-height: 1.2;
}

.user-badges {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.badge-item {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 10px;
  border-radius: 8px;
  font-size: 11px;
  font-weight: 600;
}

.badge-item.verified {
  background: rgba(34, 197, 94, 0.2);
  color: #86efac;
}

.badge-item svg {
  flex-shrink: 0;
}

.user-id {
  margin: 8px 0 0;
  color: rgba(226, 232, 240, 0.7);
  font-size: 13px;
  font-family: 'Courier New', monospace;
}

.logout-btn {
  position: absolute;
  top: 16px;
  right: 16px;
  z-index: 2;
  display: flex;
  align-items: center;
  gap: 6px;
  height: 32px;
  padding: 0 12px;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  color: rgba(255, 255, 255, 0.9);
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.logout-btn:hover {
  background: rgba(255, 255, 255, 0.15);
  border-color: rgba(255, 255, 255, 0.25);
}

.logout-btn svg {
  flex-shrink: 0;
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
  padding: 0;
  overflow: hidden;
}

.order-card + .order-card {
  margin-top: 14px;
}

.order-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding: 16px 16px 12px;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-bottom: 1px solid #e2e8f0;
}

.order-caption {
  display: block;
  color: #94a3b8;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.order-time {
  display: block;
  margin-top: 4px;
  color: #0f172a;
  font-size: 14px;
  font-weight: 600;
}

.order-state {
  display: flex;
  align-items: center;
  gap: 10px;
}

.state-text {
  display: inline-flex;
  align-items: center;
  min-height: 26px;
  padding: 0 10px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 700;
}

.state-text.pending {
  background: #fef3c7;
  color: #b45309;
}

.state-text.paid {
  background: #d1fae5;
  color: #065f46;
}

.trash-btn {
  border: none;
  background: transparent;
  color: #94a3b8;
  font-size: 11px;
  cursor: pointer;
  padding: 4px 8px;
}

.order-body {
  display: flex;
  gap: 12px;
  align-items: center;
  padding: 16px;
}

.order-cover {
  width: 70px;
  height: 70px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 24px;
  font-weight: 800;
  flex-shrink: 0;
}

.order-cover-img {
  width: 70px;
  height: 70px;
  border-radius: 12px;
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
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.order-info h3 {
  margin: 0;
  color: #0f172a;
  font-size: 15px;
  font-weight: 600;
  line-height: 1.4;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.order-info p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 12px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.order-info strong {
  display: block;
  margin-top: 6px;
  color: #f97316;
  font-size: 20px;
  font-weight: 700;
  font-family: Arial, sans-serif;
}

.order-info strong::before {
  content: '¥';
  font-size: 14px;
  margin-right: 2px;
}

.order-action {
  display: flex;
  align-items: center;
  flex-shrink: 0;
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
  flex-direction: column;
  gap: 0;
  padding: 0;
  border-radius: 20px;
  background: #fff;
  box-shadow: 0 4px 12px rgba(148, 163, 184, 0.12);
  overflow: hidden;
  border: 1px solid rgba(226, 232, 240, 0.8);
}

.product-card + .product-card {
  margin-top: 14px;
}

.product-main {
  display: flex;
  flex-direction: column;
  align-items: stretch;
  gap: 0;
  min-width: 0;
  flex: 1;
}

.product-cover {
  width: 100%;
  height: 180px;
  border-radius: 0;
  overflow: hidden;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #eef4ff, #dbeafe);
  color: #2563eb;
  font-size: 48px;
  font-weight: 800;
  position: relative;
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
  padding: 16px;
}

.product-heading {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 10px;
}

.product-heading h3 {
  margin: 0;
  color: #0f172a;
  font-size: 17px;
  line-height: 1.4;
  font-weight: 600;
  flex: 1;
}

.product-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  flex-wrap: wrap;
}

.product-category,
.product-tag {
  display: inline-flex;
  align-items: center;
  min-height: 26px;
  padding: 0 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
}

.product-category {
  background: rgba(59, 130, 246, 0.1);
  color: #2563eb;
}

.product-tag {
  background: rgba(148, 163, 184, 0.12);
  color: #64748b;
}

.product-price {
  display: block;
  margin-top: 8px;
  color: #ff4d4f;
  font-size: 26px;
  line-height: 1;
  font-weight: 700;
  font-family: Arial, sans-serif;
}

.product-price::before {
  content: '¥';
  font-size: 18px;
  margin-right: 2px;
}

.product-status {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 12px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 700;
  flex-shrink: 0;
}

.product-status.approved {
  background: #10b981;
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
  height: 36px;
  padding: 0 20px;
  border-radius: 18px;
  background: linear-gradient(135deg, #fb923c, #f97316);
  color: #fff;
  font-size: 13px;
  font-weight: 700;
  box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3);
  transition: all 0.2s;
  white-space: nowrap;
}

.pay-btn:active {
  transform: scale(0.98);
}

.paid-tag {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 12px;
  border-radius: 14px;
  background: #d1fae5;
  color: #065f46;
  font-size: 12px;
  font-weight: 700;
  white-space: nowrap;
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

.settlement-actions {
  display: flex;
  gap: 12px;
  margin-top: 24px;
}

.settlement-modal {
  max-width: 480px;
  max-height: 90vh;
  overflow-y: auto;
}

.balance-display {
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
  border: 1px solid rgba(56, 189, 248, 0.2);
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 24px;
  text-align: center;
}

.balance-label {
  display: block;
  color: #64748b;
  font-size: 14px;
  margin-bottom: 8px;
}

.balance-value {
  display: block;
  color: #22c55e;
  font-size: 32px;
  font-weight: 800;
  font-family: Arial, sans-serif;
}

.settlement-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.settlement-card {
  border: 1px solid rgba(203, 213, 225, 0.6);
  border-radius: 20px;
  padding: 20px;
  background: rgba(255, 255, 255, 0.92);
  box-shadow: 0 4px 12px rgba(148, 163, 184, 0.08);
}

.settlement-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding-bottom: 16px;
  border-bottom: 1px dashed #e5e7eb;
}

.settlement-label {
  display: block;
  color: #94a3b8;
  font-size: 12px;
  margin-bottom: 4px;
}

.settlement-time {
  display: block;
  color: #0f172a;
  font-size: 14px;
}

.settlement-status {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
}

.settlement-status.pending {
  background: rgba(251, 191, 36, 0.14);
  color: #b45309;
}

.settlement-status.approved {
  background: rgba(34, 197, 94, 0.12);
  color: #15803d;
}

.settlement-status.rejected {
  background: rgba(239, 68, 68, 0.12);
  color: #dc2626;
}

.settlement-body {
  padding-top: 16px;
}

.settlement-amount-section {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

.settlement-amount-label {
  color: #64748b;
  font-size: 14px;
}

.settlement-amount {
  color: #22c55e;
  font-size: 24px;
  font-weight: 800;
  font-family: Arial, sans-serif;
}

.settlement-certificate {
  margin-bottom: 16px;
}

.certificate-label {
  display: block;
  color: #64748b;
  font-size: 13px;
  margin-bottom: 8px;
}

.certificate-img {
  width: 120px;
  height: 120px;
  border-radius: 12px;
  object-fit: cover;
  border: 1px solid #e5e7eb;
  cursor: pointer;
}

.settlement-remark {
  padding: 12px;
  background: #fef3c7;
  border-radius: 12px;
}

.settlement-remark.rejected {
  background: #fee2e2;
  border: 1px solid #fecaca;
}

.remark-label {
  display: block;
  color: #92400e;
  font-size: 12px;
  font-weight: 600;
  margin-bottom: 6px;
}

.settlement-remark.rejected .remark-label {
  color: #991b1b;
}

.remark-text {
  color: #78350f;
  font-size: 13px;
  line-height: 1.6;
  margin: 0;
}

.settlement-remark.rejected .remark-text {
  color: #dc2626;
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

.modal-btn.primary {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: #fff;
  box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
}

.modal-btn.primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
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

}

@media (max-width: 768px) {
  .profile-shell {
    width: 100%;
    padding: 0 0 92px;
  }

  .hero-card {
    border-radius: 0 0 24px 24px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.2);
  }

  .hero-inner {
    padding: 24px 16px;
  }

  .hero-main {
    gap: 16px;
  }

  .avatar-frame {
    width: 70px;
    height: 70px;
  }

  .avatar-core {
    font-size: 28px;
  }

  .user-name-section h1 {
    font-size: 20px;
  }

  .user-id {
    font-size: 12px;
  }

  .logout-btn {
    top: 12px;
    right: 12px;
    height: 28px;
    padding: 0 10px;
    font-size: 11px;
  }

  .logout-btn svg {
    width: 12px;
    height: 12px;
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
  .user-name-section h1 {
    font-size: 18px;
  }

  .user-name-section {
    flex-direction: column;
    align-items: flex-start;
    gap: 6px;
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
