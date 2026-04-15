import { getValidToken, removeToken } from "./auth";

export const PROFILE_KEY = "shopping-control-profile";
export const PAYMENT_SUMMARY_KEY = "shopping-control-payment-summary";
export const HOME_CART_KEY = "shopping-control-home-cart";
export const CURRENT_ORDER_KEY = "shopping-control-current-order";

const DEFAULT_PAYMENT_SUMMARY = {
  amount: 0,
  count: 0,
  items: []
};

const DEFAULT_HOME_CART_STATE = {
  activeCategoryId: null,
  items: {}
};

export function isLoggedIn() {
  return Boolean(getValidToken());
}

export function saveProfile(profile = {}) {
  localStorage.setItem(PROFILE_KEY, JSON.stringify(profile));
}

export function clearProfile() {
  localStorage.removeItem(PROFILE_KEY);
}

export function clearAuth() {
  removeToken();
  clearProfile();
}

export function setPaymentSummary(summary = {}) {
  localStorage.setItem(PAYMENT_SUMMARY_KEY, JSON.stringify(summary));
}

export function getPaymentSummary() {
  try {
    const stored = localStorage.getItem(PAYMENT_SUMMARY_KEY);
    return stored ? { ...DEFAULT_PAYMENT_SUMMARY, ...JSON.parse(stored) } : DEFAULT_PAYMENT_SUMMARY;
  } catch (error) {
    return DEFAULT_PAYMENT_SUMMARY;
  }
}

export function getHomeCartState() {
  try {
    const stored = localStorage.getItem(HOME_CART_KEY);

    if (!stored) {
      return DEFAULT_HOME_CART_STATE;
    }

    const parsed = JSON.parse(stored);
    return {
      activeCategoryId: parsed.activeCategoryId || null,
      items: parsed.items && typeof parsed.items === "object" ? parsed.items : {}
    };
  } catch (error) {
    return DEFAULT_HOME_CART_STATE;
  }
}

export function setHomeCartState(state = {}) {
  localStorage.setItem(
    HOME_CART_KEY,
    JSON.stringify({
      activeCategoryId: state.activeCategoryId || null,
      items: state.items && typeof state.items === "object" ? state.items : {}
    })
  );
}

export function setCurrentOrder(order = {}) {
  localStorage.setItem(CURRENT_ORDER_KEY, JSON.stringify(order));
}

export function getCurrentOrder() {
  try {
    const stored = localStorage.getItem(CURRENT_ORDER_KEY);
    return stored ? JSON.parse(stored) : null;
  } catch (error) {
    return null;
  }
}

export function clearCurrentOrder() {
  localStorage.removeItem(CURRENT_ORDER_KEY);
}
