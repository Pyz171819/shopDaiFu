/**
 * WeChat JS-SDK helpers for share-to-friend and timeline.
 */

let wechatSdkPromise = null

const DEFAULT_JS_API_LIST = [
  'updateAppMessageShareData',
  'updateTimelineShareData',
  'onMenuShareAppMessage',
  'onMenuShareTimeline'
]

function isAbsoluteUrl(url) {
  return /^https?:\/\//i.test(url)
}

function toAbsoluteUrl(url) {
  if (!url) {
    return ''
  }

  if (isAbsoluteUrl(url)) {
    return url
  }

  if (url.startsWith('//')) {
    return `${window.location.protocol}${url}`
  }

  if (url.startsWith('/')) {
    return `${window.location.origin}${url}`
  }

  return `${window.location.origin}/${url.replace(/^\.?\//, '')}`
}

function upsertMeta(selector, createMeta) {
  let meta = document.head.querySelector(selector)

  if (!meta) {
    meta = createMeta()
    document.head.appendChild(meta)
  }

  return meta
}

function updateShareMeta(shareData) {
  if (shareData.title) {
    document.title = shareData.title
  }

  const description = upsertMeta('meta[name="description"]', () => {
    const meta = document.createElement('meta')
    meta.setAttribute('name', 'description')
    return meta
  })
  description.setAttribute('content', shareData.desc || '')

  const ogTitle = upsertMeta('meta[property="og:title"]', () => {
    const meta = document.createElement('meta')
    meta.setAttribute('property', 'og:title')
    return meta
  })
  ogTitle.setAttribute('content', shareData.title || '')

  const ogDescription = upsertMeta('meta[property="og:description"]', () => {
    const meta = document.createElement('meta')
    meta.setAttribute('property', 'og:description')
    return meta
  })
  ogDescription.setAttribute('content', shareData.desc || '')

  const ogImage = upsertMeta('meta[property="og:image"]', () => {
    const meta = document.createElement('meta')
    meta.setAttribute('property', 'og:image')
    return meta
  })
  ogImage.setAttribute('content', shareData.imgUrl || '')

  const ogUrl = upsertMeta('meta[property="og:url"]', () => {
    const meta = document.createElement('meta')
    meta.setAttribute('property', 'og:url')
    return meta
  })
  ogUrl.setAttribute('content', shareData.link || '')
}

function normalizeShareData(shareData = {}) {
  const title = (shareData.title || document.title || '').trim()
  const desc = (shareData.desc || '').trim()
  const link = toAbsoluteUrl(shareData.link || window.location.href.split('#')[0])
  const imgUrl = toAbsoluteUrl(shareData.imgUrl || '')

  const normalized = {
    title,
    desc,
    link,
    imgUrl
  }

  updateShareMeta(normalized)

  return normalized
}

function bindShareApi(wx, apiName, payload) {
  if (typeof wx[apiName] !== 'function') {
    return false
  }

  try {
    wx[apiName]({
      ...payload,
      fail(err) {
        console.error(`[wechat] ${apiName} fail`, err)
      }
    })
    return true
  } catch (err) {
    console.error(`[wechat] ${apiName} bind error`, err)
    return false
  }
}

export function loadWechatSDK() {
  if (window.wx) {
    return Promise.resolve(window.wx)
  }

  if (wechatSdkPromise) {
    return wechatSdkPromise
  }

  wechatSdkPromise = new Promise((resolve, reject) => {
    const script = document.createElement('script')
    script.src = 'https://res2.wx.qq.com/open/js/jweixin-1.6.0.js'

    script.onload = () => {
      if (window.wx) {
        resolve(window.wx)
        return
      }

      wechatSdkPromise = null
      reject(new Error('WeChat JS-SDK loaded but window.wx is missing'))
    }

    script.onerror = () => {
      wechatSdkPromise = null
      reject(new Error('Failed to load WeChat JS-SDK'))
    }

    document.head.appendChild(script)
  })

  return wechatSdkPromise
}

export async function initWechatConfig(config, jsApiList = []) {
  const wx = await loadWechatSDK()
  const finalJsApiList = Array.from(
    new Set(jsApiList.length > 0 ? [...DEFAULT_JS_API_LIST, ...jsApiList] : DEFAULT_JS_API_LIST)
  )

  return new Promise((resolve, reject) => {
    let settled = false
    let readyTimer = null

    const resolveSafely = () => {
      if (settled) {
        return
      }
      settled = true
      resolve(wx)
    }

    const rejectSafely = (err) => {
      if (settled) {
        return
      }
      settled = true
      if (readyTimer) {
        clearTimeout(readyTimer)
        readyTimer = null
      }
      reject(err)
    }

    try {
      wx.config({
        debug: false,
        appId: config.appId,
        timestamp: config.timestamp,
        nonceStr: config.nonceStr,
        signature: config.signature,
        jsApiList: finalJsApiList
      })

      wx.ready(() => {
        // Mobile WeChat may emit ready first and error right after.
        readyTimer = setTimeout(() => {
          resolveSafely()
        }, 400)
      })

      wx.error((err) => {
        console.error('[wechat] wx.error', err)
        rejectSafely(err)
      })
    } catch (err) {
      rejectSafely(err)
    }
  })
}

export async function setShareToFriend(shareData) {
  const wx = window.wx

  if (!wx) {
    throw new Error('WeChat JS-SDK is not initialized')
  }

  const normalized = normalizeShareData(shareData)
  const friendPayload = {
    title: normalized.title,
    desc: normalized.desc,
    link: normalized.link,
    imgUrl: normalized.imgUrl
  }
  const timelinePayload = {
    title: normalized.title,
    link: normalized.link,
    imgUrl: normalized.imgUrl
  }

  let bound = false

  const hasModernFriendApi = typeof wx.updateAppMessageShareData === 'function'
  const hasModernTimelineApi = typeof wx.updateTimelineShareData === 'function'

  if (hasModernFriendApi) {
    bound = bindShareApi(wx, 'updateAppMessageShareData', friendPayload) || bound
  } else {
    bound = bindShareApi(wx, 'onMenuShareAppMessage', {
      ...friendPayload,
      type: 'link',
      dataUrl: ''
    }) || bound
  }

  if (hasModernTimelineApi) {
    bound = bindShareApi(wx, 'updateTimelineShareData', timelinePayload) || bound
  } else {
    bound = bindShareApi(wx, 'onMenuShareTimeline', timelinePayload) || bound
  }

  if (!bound) {
    throw new Error('No available WeChat share API on this client')
  }

  return normalized
}
