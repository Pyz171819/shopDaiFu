import axios from "axios";
import { getValidToken } from "./auth";
import { clearAuth } from "./app-state";
import router from "@/router";

const service = axios.create({
  baseURL: "/api",
  timeout: 10000,
  headers: {
    "Content-Type": "application/json"
  }
});

service.interceptors.request.use(
  config => {
    const token = getValidToken();

    if (token && config.url !== "/login") {
      config.headers.Authorization = "Bearer " + token;
    }

    return config;
  },
  error => {
    return Promise.reject(error);
  }
);

service.interceptors.response.use(
  response => {
    const res = response.data;
    
    // 统一检查响应中的code字段
    // 若依框架中,401表示未授权
    if (res && (res.code === 401 || res.code === '401')) {
      clearAuth();
      
      if (router.currentRoute.name !== "login") {
        router.replace({ name: "login" }).catch(() => {});
      }
      
      return Promise.reject(new Error(res.msg || "登录已过期，请重新登录"));
    }
    
    return res;
  },
  error => {
    // 处理HTTP状态码401
    if (error.response && error.response.status === 401) {
      clearAuth();
      
      if (router.currentRoute.name !== "login") {
        router.replace({ name: "login" }).catch(() => {});
      }
      
      return Promise.reject(new Error("登录已过期，请重新登录"));
    }

    // 处理响应体中的401 code
    if (error.response && error.response.data) {
      const data = error.response.data;
      if (data.code === 401 || data.code === '401') {
        clearAuth();
        
        if (router.currentRoute.name !== "login") {
          router.replace({ name: "login" }).catch(() => {});
        }
        
        return Promise.reject(new Error(data.msg || "登录已过期，请重新登录"));
      }
    }

    const message =
      (error.response &&
        error.response.data &&
        (error.response.data.msg || error.response.data.message)) ||
      error.message ||
      "请求失败";

    return Promise.reject(new Error(message));
  }
);

export default service;

