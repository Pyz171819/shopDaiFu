import request from "@/utils/request";

/**
 * 获取微信 JS-SDK 签名配置
 * @param {string} url - 当前页面URL
 */
export function getJsapiSignature(url) {
  return request({
    url: "/biz/wechat/jsapi/signature",
    method: "get",
    params: {
      url
    }
  });
}
