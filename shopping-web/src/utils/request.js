import axios from "axios";
import { getValidToken } from "./auth";

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
    return response.data;
  },
  error => {
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

