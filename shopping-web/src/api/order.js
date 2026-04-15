import request from "@/utils/request";

// 创建订单
export function createOrder(data) {
  return request({
    url: "/biz/order/create",
    method: "post",
    data
  });
}

// 根据订单号查询订单详情
export function getOrderDetail(outTradeNo) {
  return request({
    url: "/biz/order/query",
    method: "get",
    params: {
      outTradeNo
    }
  });
}

// 发起支付
export function payOrder(data) {
  return request({
    url: "/biz/order/pay",
    method: "post",
    data
  });
}
