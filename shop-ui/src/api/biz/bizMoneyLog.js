import request from '@/utils/request'

// 查询资金变动日志列表
export function listBizMoneyLog(query) {
  return request({
    url: '/biz/bizMoneyLog/list',
    method: 'get',
    params: query
  })
}

// 查询资金变动日志详细
export function getBizMoneyLog(id) {
  return request({
    url: '/biz/bizMoneyLog/' + id,
    method: 'get'
  })
}

// 新增资金变动日志
export function addBizMoneyLog(data) {
  return request({
    url: '/biz/bizMoneyLog',
    method: 'post',
    data: data
  })
}

// 修改资金变动日志
export function updateBizMoneyLog(data) {
  return request({
    url: '/biz/bizMoneyLog',
    method: 'put',
    data: data
  })
}

// 删除资金变动日志
export function delBizMoneyLog(id) {
  return request({
    url: '/biz/bizMoneyLog/' + id,
    method: 'delete'
  })
}
