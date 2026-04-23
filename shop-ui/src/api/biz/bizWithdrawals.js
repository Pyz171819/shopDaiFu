import request from '@/utils/request'

// 查询提现申请列表
export function listBizWithdrawals(query) {
  return request({
    url: '/biz/bizWithdrawals/list',
    method: 'get',
    params: query
  })
}

// 查询提现申请详细
export function getBizWithdrawals(id) {
  return request({
    url: '/biz/bizWithdrawals/' + id,
    method: 'get'
  })
}

// 新增提现申请
export function addBizWithdrawals(data) {
  return request({
    url: '/biz/bizWithdrawals',
    method: 'post',
    data: data
  })
}

// 修改提现申请
export function updateBizWithdrawals(data) {
  return request({
    url: '/biz/bizWithdrawals',
    method: 'put',
    data: data
  })
}

// 删除提现申请
export function delBizWithdrawals(id) {
  return request({
    url: '/biz/bizWithdrawals/' + id,
    method: 'delete'
  })
}

// 提现审核
export function auditBizWithdrawals(data) {
  return request({
    url: '/biz/bizWithdrawals/audit',
    method: 'put',
    data: data
  })
}
