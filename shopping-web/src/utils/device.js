/**
 * 设备和浏览器环境检测工具
 */

/**
 * 判断是否为移动设备
 */
export function isMobile() {
  const ua = navigator.userAgent
  return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(ua)
}

/**
 * 判断是否为微信内置浏览器
 */
export function isWechat() {
  const ua = navigator.userAgent.toLowerCase()
  return /micromessenger/i.test(ua)
}

/**
 * 获取客户端环境类型（用于支付接口）
 * @returns {string} 'wechat_h5' | 'mobile_h5' | 'pc'
 */
export function getClientType() {
  if (isWechat()) {
    return 'wechat_h5' // 微信内置浏览器
  } else if (isMobile()) {
    return 'mobile_h5' // 手机外部浏览器
  } else {
    return 'pc' // PC浏览器
  }
}

/**
 * 获取设备类型
 * @returns {string} 'mobile' | 'pc'
 */
export function getDeviceType() {
  return isMobile() ? 'mobile' : 'pc'
}

/**
 * 获取浏览器类型
 * @returns {string} 'wechat' | 'external'
 */
export function getBrowserType() {
  return isWechat() ? 'wechat' : 'external'
}
