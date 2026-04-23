import Vue from "vue";
import Router from "vue-router";
import LoginPage from "../views/login/index.vue";
import HomePage from "../views/home/index.vue";
import PaymentPage from "../views/payment/index.vue";
import ProfilePage from "../views/profile/index.vue";
import CashierPage from "../views/cashier/index.vue";
import { isLoggedIn, clearAuth } from "../utils/app-state";

Vue.use(Router);

const router = new Router({
  // mode: "hash",
  mode: "history",
  base: "/web",
  routes: [
    {
      path: "/",
      redirect: () => (isLoggedIn() ? "/home" : "/login")
    },
    {
      path: "/login",
      name: "login",
      component: LoginPage,
      meta: {
        guestOnly: true,
        title: "登录 - 代付系统"
      }
    },
    {
      path: "/home",
      name: "home",
      component: HomePage,
      meta: {
        requiresAuth: true,
        title: "商品列表 - 代付系统"
      }
    },
    {
      path: "/payment",
      name: "payment",
      component: PaymentPage,
      meta: {
        requiresAuth: true,
        title: "选择模板 - 代付系统"
      }
    },
    {
      path: "/cashier",
      name: "cashier",
      component: CashierPage,
      meta: {
        requiresAuth: true,
        title: "收银台 - 代付系统"
      }
    },
    {
      path: "/profile",
      name: "profile",
      component: ProfilePage,
      meta: {
        requiresAuth: true,
        title: "个人中心 - 代付系统"
      }
    },
    {
      path: "*",
      redirect: "/"
    }
  ]
});

router.beforeEach((to, from, next) => {
  const loggedIn = isLoggedIn();

  // 如果token无效,清除所有认证信息
  if (!loggedIn && localStorage.getItem('Admin-Token')) {
    clearAuth();
  }

  if (to.matched.some(record => record.meta.requiresAuth) && !loggedIn) {
    next({ name: "login" });
    return;
  }

  if (to.matched.some(record => record.meta.guestOnly) && loggedIn) {
    next({ name: "home" });
    return;
  }

  // 设置页面标题
  if (to.meta.title) {
    document.title = to.meta.title;
  } else {
    document.title = "代付系统";
  }

  next();
});

export default router;
