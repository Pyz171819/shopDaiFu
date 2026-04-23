import request from '@/utils/request'

// 查询全部卡片模板
export function listAllShareCardConfig() {
    return request({
        url: '/biz/bizShareCardConfig/listAll',
        method: 'get'
    })
}

// 根据模板ID查询分享卡片配置
export function getShareCardConfigByTpl(tpl) {
    return request({
        url: `/biz/bizShareCardConfig/getTpl/${tpl}`,
        method: 'get'
    })
}
  