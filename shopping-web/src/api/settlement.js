import request from "@/utils/request";

// 提交提现申请
export function applySettlement(data) {
  return request({
    url: "/biz/bizWithdrawals",
    method: "post",
    data
  });
}

// 获取结算记录列表
export function getSettlementList(userId) {
  return request({
    url: "/biz/bizWithdrawals/listAllByUserId",
    method: "get",
    params: {
      userId
    }
  });
}

// 获取账户余额
export function getAccountBalance() {
  return request({
    url: "/biz/account/balance",
    method: "get"
  });
}
