import Vue from "vue";
import Router from "vue-router";
import LoginPage from "../views/login/index.vue";
import HomePage from "../views/home/index.vue";
import PaymentPage from "../views/payment/index.vue";
import ProfilePage from "../views/profile/index.vue";
import CashierPage from "../views/cashier/index.vue";
import { isLoggedIn } from "../utils/app-state";

Vue.use(Router);

const router = new Router({
  mode: "hash",
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
        guestOnly: true
      }
    },
    {
      path: "/home",
      name: "home",
      component: HomePage,
      meta: {
        requiresAuth: true
      }
    },
    {
      path: "/payment",
      name: "payment",
      component: PaymentPage,
      meta: {
        requiresAuth: true
      }
    },
    {
      path: "/cashier",
      name: "cashier",
      component: CashierPage,
      meta: {
        requiresAuth: true
      }
    },
    {
      path: "/profile",
      name: "profile",
      component: ProfilePage,
      meta: {
        requiresAuth: true
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

  if (to.matched.some(record => record.meta.requiresAuth) && !loggedIn) {
    next({ name: "login" });
    return;
  }

  if (to.matched.some(record => record.meta.guestOnly) && loggedIn) {
    next({ name: "home" });
    return;
  }

  next();
});

export default router;
