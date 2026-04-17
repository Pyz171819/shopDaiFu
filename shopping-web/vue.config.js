const { defineConfig } = require("@vue/cli-service");

module.exports = defineConfig({
  transpileDependencies: true,
  devServer: {
    host: '0.0.0.0', // 允许外部访问
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
