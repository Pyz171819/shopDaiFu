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

// 获取我的订单列表
export function getMyOrders(status, orderCreateUserId) {
  const params = {};
  // status: null/undefined-全部, 0-待支付, 1-已支付
  if (status !== null && status !== undefined) {
    params.status = status;
  }
  // 订单创建人ID
  if (orderCreateUserId) {
    params.orderCreateUserId = orderCreateUserId;
  }
  return request({
    url: "/biz/order/queryByStatus",
    method: "get",
    params
  });
}

// 查询今日流水
export function getTodayRevenue(createBy) {
  return request({
    url: "/biz/order/queryTodayXF",
    method: "get",
    params: {
      createBy
    }
  });
}

// 删除订单
export function deleteOrder(outTradeNo) {
  return request({
    url: "/biz/order/deleteOrder",
    method: "get",
    params: {
      outTradeNo
    }
  });
}
