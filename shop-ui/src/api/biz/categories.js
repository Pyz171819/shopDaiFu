import request from '@/utils/request'

// 查询商品分类列表
export function listCategories(query) {
  return request({
    url: '/biz/categories/list',
    method: 'get',
    params: query
  })
}

// 查询商品分类详细
export function getCategories(id) {
  return request({
    url: '/biz/categories/' + id,
    method: 'get'
  })
}

// 新增商品分类
export function addCategories(data) {
  return request({
    url: '/biz/categories',
    method: 'post',
    data: data
  })
}

// 修改商品分类
export function updateCategories(data) {
  return request({
    url: '/biz/categories',
    method: 'put',
    data: data
  })
}

// 删除商品分类
export function delCategories(id) {
  return request({
    url: '/biz/categories/' + id,
    method: 'delete'
  })
}
