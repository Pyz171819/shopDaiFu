import request from '@/utils/request'

// 查询订单列表
export function listBizOrder(query) {
  return request({
    url: '/biz/order/listByRY',
    method: 'get',
    params: query
  })
}

// 查询订单详细
export function getBizOrder(id) {
  return request({
    url: '/biz/order/ByRY/' + id,
    method: 'get'
  })
}

// 新增订单
export function addBizOrder(data) {
  return request({
    url: '/biz/order/ByRY',
    method: 'post',
    data: data
  })
}

// 修改订单
export function updateBizOrder(data) {
  return request({
    url: '/biz/order/ByRY',
    method: 'put',
    data: data
  })
}

// 删除订单
export function delBizOrder(id) {
  return request({
    url: '/biz/order/ByRY/' + id,
    method: 'delete'
  })
}
