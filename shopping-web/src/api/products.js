import request from "../utils/request";

export function getMyProducts() {
  return request({
    url: "/biz/products/getMyProducts",
    method: "get"
  });
}

export function createProduct(data) {
  return request({
    url: "/biz/products",
    method: "post",
    data
  });
}
