import request from '@/utils/request'

// 查询分享卡片配置列表
export function listBizShareCardConfig(query) {
  return request({
    url: '/biz/bizShareCardConfig/list',
    method: 'get',
    params: query
  })
}

// 查询分享卡片配置详细
export function getBizShareCardConfig(id) {
  return request({
    url: '/biz/bizShareCardConfig/' + id,
    method: 'get'
  })
}

// 新增分享卡片配置
export function addBizShareCardConfig(data) {
  return request({
    url: '/biz/bizShareCardConfig',
    method: 'post',
    data: data
  })
}

// 修改分享卡片配置
export function updateBizShareCardConfig(data) {
  return request({
    url: '/biz/bizShareCardConfig',
    method: 'put',
    data: data
  })
}

// 删除分享卡片配置
export function delBizShareCardConfig(id) {
  return request({
    url: '/biz/bizShareCardConfig/' + id,
    method: 'delete'
  })
}
