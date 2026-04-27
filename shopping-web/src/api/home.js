import request from "../utils/request";

// 查询分类列表
export function listCategory() {
    return request({
        url: "/biz/categories/listAll",
        method: "get"
    });
}

// 根据分类查询商品列表
export function listProductByCategory(categoryId) {
    return request({
        url: "/biz/products/listAll",
        method: "get",
        params: {
            categoryId
        }
    });
}

export function searchHomeProducts(params) {
    return request({
        url: "/biz/products/searchHome",
        method: "get",
        params
    });
}

export function getHomeConfig() {
    return request({
        url: "/biz/config/common",
        method: "get"
    });
}

export function listHomeSlides() {
    return request({
        url: "/biz/slides/listAll",
        method: "get"
    });
}
