import request from '@/utils/request'

// 查询全部卡片模板
export function listAllShareCardConfig() {
    return request({
        url: '/biz/bizShareCardConfig/listAll',
        method: 'get'
    })
}