# shopping-control-system

## 项目介绍

`shopping-control-system` 是一个基于 RuoYi Vue 体系二次开发的商城中控/代付管理系统，包含后台管理端、业务服务模块和前台 H5 页面两部分。

- 后台管理端：用于系统配置、权限管理、业务数据维护、订单管理、提现审核、支付配置、轮播配置等日常运营工作。
- 前台 H5：用于用户登录、商品浏览、支付下单、模板收银台展示、个人中心等场景。
- 后端服务：提供统一的权限认证、业务接口、定时任务、代码生成、缓存和系统监控能力。

项目整体采用前后端分离方式开发，适合用作商城中控、支付演示、后台管理类项目的学习参考。

## 开源声明

本项目仓库包含 `LICENSE` 文件，当前采用 `MIT License` 开源协议。

在保留原始版权声明和许可协议的前提下，你可以基于本项目进行学习、阅读、修改和二次开发。

## 非商业学习用途声明

本项目文档仅建议将当前仓库作为：

- Java 后台管理系统学习示例
- 前后端分离项目练习示例
- 支付流程与收银台页面实现参考
- 管理后台功能扩展示例

请勿将本项目直接用于任何未经授权的商业运营、灰色支付、违规代收代付或其他违法用途。

如需商用，请先自行确认：

- 代码来源与依赖组件许可是否满足商用条件
- 支付、订单、用户数据处理是否符合当地法律法规
- 你已具备对系统安全、隐私保护和运维风险的独立承担能力

## 技术栈

### 后端

- Java 17
- Spring Boot 4.0.3
- Spring Security
- MyBatis
- MySQL 8.x
- Redis
- Druid
- Quartz
- Fastjson2
- SpringDoc OpenAPI
- Maven 多模块管理

### 前端

#### 管理端 `shop-ui`

- Vue 2.6
- Vue Router
- Vuex
- Element UI
- Axios
- ECharts

#### H5 端 `shopping-web`

- Vue 2.7
- Vue Router
- Element UI
- Axios
- html2canvas
- qrcodejs2

## 项目结构

```text
shopping-control-system
├─ shop-admin        # 启动入口、Web 配置、接口聚合
├─ shop-framework    # 安全、鉴权、AOP、全局配置
├─ shop-system       # 系统管理模块
├─ shop-common       # 通用工具、公共组件、基础封装
├─ shop-biz          # 业务模块
├─ shop-quartz       # 定时任务模块
├─ shop-generator    # 代码生成模块
├─ shop-ui           # 后台管理端前端
└─ shopping-web      # H5/分享收银台前端
```

## 功能模块

### 系统功能

- 用户管理
- 角色管理
- 菜单管理
- 部门管理
- 岗位管理
- 字典管理
- 通知公告
- 登录日志
- 操作日志
- 在线用户
- 服务监控
- 缓存监控
- 定时任务
- 代码生成

### 业务功能

- 数据看板
- 商品分类管理
- 商品管理
- 订单管理
- 支付配置
- 系统业务配置
- 轮播图管理
- 分享卡片配置
- 资金流水管理
- 提现管理
- 微信支付相关接口
- 分享收银台模板页面

### H5 功能

- 登录
- 商品列表/首页
- 支付页
- 收银台模板展示
- 个人中心

## 启动步骤

## 1. 环境准备

请先准备以下环境：

- JDK 17
- Maven 3.9+
- MySQL 8.x
- Redis 6.x 或以上
- Node.js 16+，npm 8+

## 2. 数据库与缓存配置

后端默认配置位于 `shop-admin/src/main/resources/`：

- 服务端口：`8989`
- MySQL 数据库：`shop_control`
- MySQL 地址：`localhost:3306`
- Redis 地址：`localhost:6379`

当前默认数据库配置来自 `application-druid.yml`：

```yml
url: jdbc:mysql://localhost:3306/shop_control
username: root
password: 
```

如果你的本地环境不同，请先修改：

- `shop-admin/src/main/resources/application.yml`
- `shop-admin/src/main/resources/application-druid.yml`

## 3. 启动后端

在仓库根目录执行：

```bash
mvn clean package
mvn -pl shop-admin -am spring-boot:run
```

启动成功后默认访问：

- 后端接口：`http://localhost:8989`
- Swagger 文档：`http://localhost:8989/swagger-ui.html`

## 4. 启动后台管理端

进入 `shop-ui` 目录执行：

```bash
npm install
npm run dev
```

默认访问地址：

- 管理端：`http://localhost:80`

开发代理已配置到：

- `/dev-api -> http://localhost:8989`

## 5. 启动 H5 前端

进入 `shopping-web` 目录执行：

```bash
npm install
npm run serve
```

默认访问地址：

- H5 端：`http://localhost:80`

说明：

- `shopping-web` 开发环境同样占用 `80` 端口。
- 如果需要同时启动两个前端，请修改其中一个项目的 `vue.config.js` 端口。
- `shopping-web` 的接口代理为 `/api -> http://localhost:8989`

## 截图

建议将项目截图统一放在仓库根目录 `docs/images/` 下，再按下面方式引用。

当前可在 README 中补充以下页面截图：

- 后台登录页
- 后台首页/数据看板
- 商品管理页
- 订单管理页
- 支付配置页
- H5 首页
- H5 支付页
- 收银台模板页

示例目录结构：

```text
docs/
└─ images/
   ├─ admin-login.png
   ├─ admin-dashboard.png
   ├─ admin-products.png
   ├─ admin-orders.png
   ├─ admin-pay-config.png
   ├─ h5-home.png
   ├─ h5-payment.png
   └─ h5-cashier.png
```

## 补充说明

- 仓库当前已经包含 `shop-ui/node_modules`、`shopping-web/node_modules` 以及部分 `target` 产物，实际部署时建议按需清理。
- 根目录下暂未发现数据库初始化 SQL 文件，首次运行前需要你自行准备 `shop_control` 数据库结构与基础数据。
- 如果要正式部署，建议单独梳理数据库账号、Redis 密码、上传目录、支付配置和跨域设置。
