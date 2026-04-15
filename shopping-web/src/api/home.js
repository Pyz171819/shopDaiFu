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