import request from '@/utils/request'

// 查询支付配置
export function getPayConfig() {
  return request({
    url: '/biz/pay/config/info',
    method: 'get'
  })
}

// 保存支付配置
export function savePayConfig(data) {
  return request({
    url: '/biz/pay/config/save',
    method: 'post',
    data: data
  })
}
