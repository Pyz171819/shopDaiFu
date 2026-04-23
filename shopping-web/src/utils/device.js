/**
 * Device and browser environment helpers.
 */

const WECHAT_ENTRY_URL_KEY = 'wechat_entry_url'

/**
 * Determine whether current device is mobile.
 */
export function isMobile() {
  const ua = navigator.userAgent
  return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(ua)
}

/**
 * Determine whether current device is iOS.
 */
export function isIOS() {
  const ua = navigator.userAgent
  return /iPhone|iPad|iPod/i.test(ua)
}

/**
 * Determine whether current browser is WeChat.
 */
export function isWechat() {
  const ua = navigator.userAgent.toLowerCase()
  return /micromessenger/i.test(ua)
}

/**
 * Get current page URL without hash.
 */
export function getCurrentPageUrl() {
  return window.location.href.split('#')[0]
}

/**
 * For WeChat JS-SDK, iOS must use the first page URL in the current session.
 */
export function getWechatSignUrl() {
  const currentUrl = getCurrentPageUrl()

  if (!isWechat()) {
    return currentUrl
  }

  try {
    const cachedUrl = sessionStorage.getItem(WECHAT_ENTRY_URL_KEY)

    if (!cachedUrl) {
      sessionStorage.setItem(WECHAT_ENTRY_URL_KEY, currentUrl)
      return currentUrl
    }

    return isIOS() ? cachedUrl : currentUrl
  } catch (error) {
    return currentUrl
  }
}

/**
 * Get client environment type for payment API.
 * @returns {string} 'wechat_h5' | 'mobile_h5' | 'pc'
 */
export function getClientType() {
  if (isWechat()) {
    return 'wechat_h5'
  } else if (isMobile()) {
    return 'mobile_h5'
  } else {
    return 'pc'
  }
}

/**
 * Get device type.
 * @returns {string} 'mobile' | 'pc'
 */
export function getDeviceType() {
  return isMobile() ? 'mobile' : 'pc'
}

/**
 * Get browser type.
 * @returns {string} 'wechat' | 'external'
 */
export function getBrowserType() {
  return isWechat() ? 'wechat' : 'external'
}
