import request from '@/utils/request'

// 查询轮播图列表
export function listBizSlides(query) {
  return request({
    url: '/biz/slides/list',
    method: 'get',
    params: query
  })
}

// 查询轮播图详细
export function getBizSlides(id) {
  return request({
    url: '/biz/slides/' + id,
    method: 'get'
  })
}

// 新增轮播图
export function addBizSlides(data) {
  return request({
    url: '/biz/slides',
    method: 'post',
    data: data
  })
}

// 修改轮播图
export function updateBizSlides(data) {
  return request({
    url: '/biz/slides',
    method: 'put',
    data: data
  })
}

// 删除轮播图
export function delBizSlides(id) {
  return request({
    url: '/biz/slides/' + id,
    method: 'delete'
  })
}
