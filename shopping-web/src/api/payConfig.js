import request from "@/utils/request";

// 查询当前启用的支付通道
export function getPayConfig() {
  return request({
    url: "/biz/pay/config/info",
    method: "get"
  });
}
