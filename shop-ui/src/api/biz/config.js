import request from '@/utils/request'

// 查询通用配置
export function getCommonConfig() {
  return request({
    url: '/biz/config/common',
    method: 'get'
  })
}

// 保存单个配置
export function saveConfig(data) {
  return request({
    url: '/biz/config/save',
    method: 'post',
    data: data
  })
}

// 批量保存配置
export function saveConfigBatch(data) {
  return request({
    url: '/biz/config/saveBatch',
    method: 'post',
    data: data
  })
}
