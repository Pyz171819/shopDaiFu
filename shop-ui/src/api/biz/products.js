import request from '@/utils/request'

// 查询商品列表
export function listProducts(query) {
  return request({
    url: '/biz/products/list',
    method: 'get',
    params: query
  })
}

// 查询商品详细
export function getProducts(id) {
  return request({
    url: '/biz/products/' + id,
    method: 'get'
  })
}

// 新增商品
export function addProducts(data) {
  return request({
    url: '/biz/products',
    method: 'post',
    data: data
  })
}

// 修改商品
export function updateProducts(data) {
  return request({
    url: '/biz/products',
    method: 'put',
    data: data
  })
}

// 删除商品
export function delProducts(id) {
  return request({
    url: '/biz/products/' + id,
    method: 'delete'
  })
}

// 商品审核
export function auditProduct(data) {
  return request({
    url: '/biz/products/updateAuditStatus',
    method: 'put',
    data: data
  })
}
