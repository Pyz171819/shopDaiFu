<template>
  <div class="login-container">
    <!-- 背景装饰 -->
    <div class="bg-decoration">
      <div class="circle circle-1"></div>
      <div class="circle circle-2"></div>
      <div class="circle circle-3"></div>
    </div>

    <!-- 添加Logo -->
    <div class="logo-container">
      <img :src="getLogoUrl()" alt="系统Logo" class="logo-image" />
    </div>
    <!-- 登录卡片 -->
    <div class="login-card">
      <div class="card-header">
        <h2 class="title">{{ title }}</h2>
        <p class="subtitle">欢迎回来，请登录您的账号</p>
      </div>

      <el-form ref="loginForm" :model="loginForm" :rules="loginRules" class="login-form">
        <!-- 账号 -->
        <el-form-item prop="username">
          <el-input
            v-model="loginForm.username"
            type="text"
            placeholder="请输入账号"
            prefix-icon="el-icon-user"
            clearable
          />
        </el-form-item>

        <!-- 密码 -->
        <el-form-item prop="password">
          <el-input
            v-model="loginForm.password"
            :type="showPassword ? 'text' : 'password'"
            placeholder="请输入密码"
            prefix-icon="el-icon-lock"
            @keyup.enter.native="handleLogin"
          >
            <i
              slot="suffix"
              :class="showPassword ? 'el-icon-view' : 'el-icon-view'"
              class="password-toggle"
              @click="showPassword = !showPassword"
            />
          </el-input>
        </el-form-item>

        <!-- 验证码 -->
        <el-form-item prop="code" v-if="captchaEnabled">
          <div class="code-row">
            <el-input
              v-model="loginForm.code"
              placeholder="请输入验证码"
              prefix-icon="el-icon-key"
              maxlength="4"
              @keyup.enter.native="handleLogin"
            />
            <div class="code-image" @click="getCode">
              <img v-if="codeUrl" :src="codeUrl" alt="验证码" />
              <span v-else class="code-loading">加载中...</span>
            </div>
          </div>
        </el-form-item>

        <!-- 记住密码 -->
        <div class="form-options">
          <el-checkbox v-model="loginForm.rememberMe">记住密码</el-checkbox>
          <a v-if="register" href="#" class="forgot-link">忘记密码？</a>
        </div>

        <!-- 登录按钮 -->
        <el-button
          type="primary"
          :loading="loading"
          class="login-btn"
          @click="handleLogin"
        >
          {{ loading ? '登录中...' : '登 录' }}
        </el-button>

        <!-- 注册链接 -->
        <div v-if="register" class="register-link">
          还没有账号？
          <router-link to="/register">立即注册</router-link>
        </div>
      </el-form>
    </div>

    <!-- 底部版权 -->
    <div class="footer">
      <span>{{ footerContent }}</span>
    </div>
  </div>
</template>

<script>
import { getCodeImg } from "@/api/login"
import Cookies from "js-cookie"
import { encrypt, decrypt } from '@/utils/jsencrypt'
import defaultSettings from '@/settings'

export default {
  name: "Login",
  data() {
    return {
      title: process.env.VUE_APP_TITLE || '管理系统',
      //footerContent: defaultSettings.footerContent || 'Copyright © 2024',
      codeUrl: "",
      showPassword: false,
      loginForm: {
        username: "",
        password: "",
        rememberMe: false,
        code: "",
        uuid: ""
      },
      loginRules: {
        username: [
          { required: true, trigger: "blur", message: "请输入您的账号" }
        ],
        password: [
          { required: true, trigger: "blur", message: "请输入您的密码" }
        ],
        code: [{ required: true, trigger: "change", message: "请输入验证码" }]
      },
      loading: false,
      captchaEnabled: true,
      register: false,
      redirect: undefined
    }
  },
  watch: {
    $route: {
      handler: function(route) {
        this.redirect = route.query && route.query.redirect
      },
      immediate: true
    }
  },
  created() {
    this.getCode()
    this.getCookie()
  },
  methods: {
    getLogoUrl() {
      const publicPath = process.env.BASE_URL || '/'
      return publicPath + 'logo.png'
    },
    getCode() {
      getCodeImg().then(res => {
        this.captchaEnabled = res.captchaEnabled === undefined ? true : res.captchaEnabled
        if (this.captchaEnabled) {
          this.codeUrl = "data:image/gif;base64," + res.img
          this.loginForm.uuid = res.uuid
        }
      })
    },
    getCookie() {
      const username = Cookies.get("username")
      const password = Cookies.get("password")
      const rememberMe = Cookies.get('rememberMe')
      this.loginForm = {
        username: username === undefined ? this.loginForm.username : username,
        password: password === undefined ? this.loginForm.password : decrypt(password),
        rememberMe: rememberMe === undefined ? false : Boolean(rememberMe),
        code: "",
        uuid: this.loginForm.uuid
      }
    },
    handleLogin() {
      this.$refs.loginForm.validate(valid => {
        if (valid) {
          this.loading = true
          if (this.loginForm.rememberMe) {
            Cookies.set("username", this.loginForm.username, { expires: 30 })
            Cookies.set("password", encrypt(this.loginForm.password), { expires: 30 })
            Cookies.set('rememberMe', this.loginForm.rememberMe, { expires: 30 })
          } else {
            Cookies.remove("username")
            Cookies.remove("password")
            Cookies.remove('rememberMe')
          }
          this.$store.dispatch("Login", this.loginForm).then(() => {
            this.$router.push({ path: this.redirect || "/" }).catch(()=>{})
          }).catch(() => {
            this.loading = false
            if (this.captchaEnabled) {
              this.getCode()
            }
          })
        }
      })
    }
  }
}
</script>

<style scoped lang="scss">
.login-container {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  overflow: hidden;
}

// 背景装饰
.bg-decoration {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  overflow: hidden;
  pointer-events: none;
}

.circle {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  animation: float 20s infinite ease-in-out;
}

.circle-1 {
  width: 300px;
  height: 300px;
  top: -150px;
  left: -150px;
  animation-delay: 0s;
}

.circle-2 {
  width: 200px;
  height: 200px;
  bottom: -100px;
  right: -100px;
  animation-delay: 5s;
}

.circle-3 {
  width: 150px;
  height: 150px;
  top: 50%;
  right: 10%;
  animation-delay: 10s;
}

@keyframes float {
  0%, 100% {
    transform: translateY(0) rotate(0deg);
  }
  50% {
    transform: translateY(-20px) rotate(180deg);
  }
}
.logo-container {
  text-align: center;
  margin-bottom: 20px;
  padding-top: 10px;
}

.logo-image {
  max-width: 420px;
  height: auto;
  display: inline-block;
  filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.1));
}

// 如果logo太大，可以调整
@media (max-width: 480px) {
  .logo-image {
    max-width: 220px;
  }
}

// 登录卡片
.login-card {
  position: relative;
  z-index: 1;
  width: 420px;
  padding: 40px;
  background: rgba(255, 255, 255, 0.95);
  border-radius: 20px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(10px);
}

.card-header {
  text-align: center;
  margin-bottom: 30px;
}

.title {
  margin: 0 0 10px;
  font-size: 28px;
  font-weight: 600;
  color: #333;
}

.subtitle {
  margin: 0;
  font-size: 14px;
  color: #666;
}

// 表单样式
.login-form {
  ::v-deep .el-form-item {
    margin-bottom: 22px;
  }

  ::v-deep .el-input__inner {
    height: 48px;
    line-height: 48px;
    border-radius: 12px;
    border: 1px solid #e0e0e0;
    padding-left: 45px;
    font-size: 14px;
    transition: all 0.3s;

    &:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
  }

  ::v-deep .el-input__prefix {
    left: 15px;
    font-size: 18px;
    color: #999;
  }

  ::v-deep .el-form-item__error {
    padding-top: 4px;
  }
}

// 验证码行
.code-row {
  display: flex;
  gap: 12px;

  .el-input {
    flex: 1;
  }
}

.code-image {
  width: 120px;
  height: 48px;
  border-radius: 12px;
  overflow: hidden;
  cursor: pointer;
  border: 1px solid #e0e0e0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f5f5f5;
  transition: all 0.3s;

  &:hover {
    border-color: #667eea;
    transform: scale(1.02);
  }

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .code-loading {
    font-size: 12px;
    color: #999;
  }
}

// 密码显示切换
.password-toggle {
  cursor: pointer;
  color: #999;
  font-size: 18px;
  transition: color 0.3s;

  &:hover {
    color: #667eea;
  }
}

// 表单选项
.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;

  ::v-deep .el-checkbox__label {
    font-size: 14px;
    color: #666;
  }

  .forgot-link {
    font-size: 14px;
    color: #667eea;
    text-decoration: none;
    transition: color 0.3s;

    &:hover {
      color: #764ba2;
    }
  }
}

// 登录按钮
.login-btn {
  width: 100%;
  height: 48px;
  font-size: 16px;
  font-weight: 600;
  border-radius: 12px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  transition: all 0.3s;

  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
  }

  &:active {
    transform: translateY(0);
  }
}

// 注册链接
.register-link {
  text-align: center;
  margin-top: 20px;
  font-size: 14px;
  color: #666;

  a {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
    margin-left: 5px;
    transition: color 0.3s;

    &:hover {
      color: #764ba2;
    }
  }
}

// 底部版权
.footer {
  position: absolute;
  bottom: 20px;
  left: 0;
  right: 0;
  text-align: center;
  color: rgba(255, 255, 255, 0.8);
  font-size: 12px;
  letter-spacing: 1px;
}

// 响应式
@media (max-width: 480px) {
  .login-card {
    width: 90%;
    padding: 30px 20px;
  }

  .title {
    font-size: 24px;
  }

  .code-image {
    width: 100px;
  }
}
</style>
