const { defineConfig } = require("@vue/cli-service");

module.exports = defineConfig({
  transpileDependencies: true,

  // 生产环境挂在 /login/ 下
  publicPath: process.env.NODE_ENV === "production" ? "/web/" : "/",

  devServer: {
    host: "0.0.0.0",
    port: 80,
    proxy: {
      "/api": {
        target: "http://localhost:8989",
        changeOrigin: true,
        pathRewrite: {
          "^/api": ""
        }
      }
    }
  }
});