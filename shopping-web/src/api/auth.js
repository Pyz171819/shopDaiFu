import request from "../utils/request";

export function loginWithRuoyi(payload = {}) {
  return request({
    url: "/login",
    method: "post",
    data: {
      username: payload.username,
      password: payload.password,
      code: "qd"
    }
  });
}

export function getUserInfo() {
  return request({
    url: "/getInfo",
    method: "get"
  });
}

export function getUserSimpleInfo() {
  return request({
    url: "/getUserSimpleInfo",
    method: "get"
  });
}