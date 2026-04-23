import request from '@/utils/request'

export function getDashboardOverview() {
  return request({
    url: '/biz/dashboard/overview',
    method: 'get'
  })
}
