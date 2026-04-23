<template>
  <div class="login-page">
    <div class="ambient ambient-left"></div>
    <div class="ambient ambient-right"></div>

    <div class="login-shell">
      <section class="hero-panel">
        <div class="hero-badge">Shopping Control System</div>
        <h1 class="hero-title">商家工作台登录</h1>
        <p class="hero-text">
          面向门店运营、订单处理与数据巡检的统一入口。
        </p>
      </section>

      <section class="form-panel">

        <div class="form-head">
          <div class="form-tag">Merchant Portal</div>
          <h2>欢迎回来</h2>
          <p>请输入账号密码登录。</p>
        </div>

        <form class="login-form" @submit.prevent="handleSubmit">
          <div class="form-item">
            <label class="label" for="username">账号</label>
            <div class="input-wrap">
              <span class="input-icon">U</span>
              <input
                id="username"
                v-model.trim="form.username"
                type="text"
                placeholder="请输入登录账号"
                autocomplete="username"
              />
            </div>
            <p v-if="errors.username" class="error-text">{{ errors.username }}</p>
          </div>

          <div class="form-item">
            <label class="label" for="password">密码</label>
            <div class="input-wrap">
              <span class="input-icon">P</span>
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="请输入登录密码"
                autocomplete="current-password"
              />
              <button
                class="toggle-text"
                type="button"
                @click="showPassword = !showPassword"
              >
                {{ showPassword ? "隐藏" : "显示" }}
              </button>
            </div>
            <p v-if="errors.password" class="error-text">{{ errors.password }}</p>
          </div>

          <button class="submit-btn" type="submit" :disabled="loading">
            <span>{{ loading ? "登录中..." : "登录系统" }}</span>
          </button>

        </form>
      </section>
    </div>
  </div>
</template>

<script>
import { loginWithRuoyi, getUserInfo } from "../../api/auth";
import { setToken } from "../../utils/auth";
import { saveProfile, clearAuth } from "../../utils/app-state";

export default {
  name: "LoginPage",
  data() {
    return {
      loading: false,
      showPassword: false,
      form: {
        username: "",
        password: ""
      },
      errors: {
        username: "",
        password: ""
      }
    };
  },
  methods: {
    showMessage(type, message) {
      this.$message({
        ...(this.$messageDefaults || {}),
        type,
        message
      });
    },

    validateForm() {
      const errors = {
        username: "",
        password: ""
      };

      if (!this.form.username) {
        errors.username = "请输入登录账号";
      }

      if (!this.form.password) {
        errors.password = "请输入登录密码";
      }

      this.errors = errors;
      return !errors.username && !errors.password;
    },

    buildProfile(user) {
      const username = user.userName || this.form.username || "商家用户";
      const nickname = user.nickName || `${username}店长`;

      return {
        userId: user.userId,
        username,
        nickname,
        avatar: user.avatar || "",
        avatarText: (nickname || username || "商").slice(0, 1).toUpperCase(),
        userType: user.userType || ""
      };
    },

    async handleSubmit() {
      if (!this.validateForm()) {
        return;
      }

      this.loading = true;

      try {
        const loginRes = await loginWithRuoyi({
          username: this.form.username,
          password: this.form.password
        });

        if (!loginRes.token) {
          throw new Error(loginRes.msg || "登录成功但未获取到 token");
        }

        setToken(loginRes.token);
        const infoRes = await getUserInfo();

        if (!infoRes.user) {
          throw new Error(infoRes.msg || "未获取到用户信息");
        }

        saveProfile(this.buildProfile(infoRes.user));

        this.showMessage("success", "登录成功，正在进入首页");

        setTimeout(() => {
          this.$router.push({ name: "home" });
        }, 260);
      } catch (error) {
        clearAuth();
        this.showMessage("error", error.message || "登录失败，请稍后重试");
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>

<style scoped>
* {
  box-sizing: border-box;
}

.login-page {
  --bg-panel: rgba(255, 250, 244, 0.82);
  --bg-card: rgba(255, 255, 255, 0.72);
  --line-soft: rgba(104, 78, 53, 0.12);
  --text-main: #2b2118;
  --text-sub: #786657;
  --text-faint: #9e8c7d;
  --accent: #c86f31;
  --accent-dark: #9f5120;
  --success: #23795b;
  --danger: #ce5142;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  overflow: hidden;
  padding: 32px;
  background:
    radial-gradient(circle at 15% 20%, rgba(200, 111, 49, 0.16), transparent 26%),
    radial-gradient(circle at 85% 80%, rgba(43, 33, 24, 0.08), transparent 24%),
    linear-gradient(135deg, #f8f1e6 0%, #efe4d3 48%, #e7dbc7 100%);
}

.ambient {
  position: absolute;
  border-radius: 50%;
  filter: blur(20px);
  opacity: 0.65;
  pointer-events: none;
}

.ambient-left {
  top: -60px;
  left: -60px;
  width: 240px;
  height: 240px;
  background: rgba(224, 149, 93, 0.22);
}

.ambient-right {
  right: -80px;
  bottom: -80px;
  width: 280px;
  height: 280px;
  background: rgba(123, 93, 68, 0.18);
}

.login-shell {
  position: relative;
  z-index: 1;
  display: grid;
  grid-template-columns: 1.08fr 0.92fr;
  width: min(1120px, 100%);
  min-height: 680px;
  margin: 0 auto;
  border: 1px solid rgba(255, 255, 255, 0.45);
  border-radius: 32px;
  overflow: hidden;
  background: var(--bg-panel);
  box-shadow: 0 28px 80px rgba(78, 56, 34, 0.18);
  backdrop-filter: blur(20px);
}

.hero-panel {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 56px 54px 48px;
  text-align: center;
  background:
    linear-gradient(180deg, rgba(255, 248, 240, 0.72), rgba(242, 231, 214, 0.9)),
    linear-gradient(140deg, rgba(200, 111, 49, 0.12), transparent 56%);
}

.hero-panel::after {
  content: "";
  position: absolute;
  right: 36px;
  bottom: 34px;
  width: 180px;
  height: 180px;
  border-radius: 28px;
  background:
    linear-gradient(145deg, rgba(255, 255, 255, 0.7), rgba(200, 111, 49, 0.14));
  border: 1px solid rgba(200, 111, 49, 0.14);
  transform: rotate(12deg);
}

.hero-badge,
.form-tag {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 34px;
  padding: 0 14px;
  border-radius: 999px;
  border: 1px solid rgba(200, 111, 49, 0.18);
  background: rgba(255, 255, 255, 0.5);
  color: var(--accent-dark);
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.hero-title {
  max-width: 420px;
  margin: 22px 0 14px;
  color: var(--text-main);
  font-size: 46px;
  line-height: 1.08;
  letter-spacing: 0.02em;
}

.hero-text {
  position: relative;
  z-index: 1;
  max-width: 470px;
  margin: 0;
  color: var(--text-sub);
  font-size: 15px;
  line-height: 1.8;
}

.hero-points {
  position: relative;
  z-index: 1;
  display: grid;
  gap: 14px;
  width: 100%;
  max-width: 470px;
  margin-top: 34px;
}

.point-card {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 18px 20px;
  border: 1px solid var(--line-soft);
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.48);
  box-shadow: 0 10px 30px rgba(119, 90, 60, 0.06);
}

.point-card strong {
  color: var(--text-main);
  font-size: 15px;
}

.point-card span {
  color: var(--text-sub);
  font-size: 13px;
  line-height: 1.7;
}

.form-panel {
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 52px 46px 42px;
  background: var(--bg-card);
  backdrop-filter: blur(16px);
}

.form-head h2 {
  margin: 18px 0 10px;
  color: var(--text-main);
  font-size: 30px;
}

.form-head p {
  margin: 0;
  color: var(--text-sub);
  font-size: 14px;
  line-height: 1.7;
}

.login-form {
  margin-top: 30px;
}

.form-item {
  margin-bottom: 18px;
}

.label {
  display: block;
  margin-bottom: 8px;
  color: var(--text-main);
  font-size: 14px;
  font-weight: 600;
}

.input-wrap {
  position: relative;
}

.input-wrap input {
  width: 100%;
  height: 52px;
  padding: 0 54px 0 48px;
  border: 1px solid rgba(120, 102, 87, 0.18);
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.9);
  color: var(--text-main);
  font-size: 14px;
  outline: none;
  transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
}

.input-wrap input:focus {
  border-color: rgba(200, 111, 49, 0.6);
  box-shadow: 0 0 0 4px rgba(200, 111, 49, 0.12);
  transform: translateY(-1px);
}

.input-icon {
  position: absolute;
  top: 50%;
  left: 18px;
  transform: translateY(-50%);
  width: 20px;
  color: var(--text-faint);
  font-size: 13px;
  font-weight: 700;
  text-align: center;
  pointer-events: none;
}

.toggle-text {
  position: absolute;
  top: 50%;
  right: 16px;
  transform: translateY(-50%);
  border: none;
  background: transparent;
  color: var(--accent-dark);
  font-size: 13px;
  cursor: pointer;
}

.option-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin: 4px 0 22px;
}

.remember {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--text-sub);
  font-size: 13px;
}

.remember input {
  width: 15px;
  height: 15px;
  accent-color: var(--accent);
}

.text-btn {
  border: none;
  padding: 0;
  background: transparent;
  color: var(--accent-dark);
  font-size: 13px;
  cursor: pointer;
}

.submit-btn {
  width: 100%;
  height: 52px;
  border: none;
  border-radius: 16px;
  background: linear-gradient(135deg, #d37a39 0%, #b55d28 100%);
  color: #fff9f3;
  font-size: 15px;
  font-weight: 700;
  letter-spacing: 0.04em;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
  box-shadow: 0 18px 30px rgba(181, 93, 40, 0.24);
}

.submit-btn:hover {
  transform: translateY(-1px);
}

.submit-btn:disabled {
  opacity: 0.72;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.error-text {
  margin: 8px 0 0;
  color: var(--danger);
  font-size: 14px;
  font-weight: 600;
  line-height: 1.5;
}

.api-hint {
  margin-top: 22px;
  padding: 16px 18px;
  border: 1px dashed rgba(200, 111, 49, 0.3);
  border-radius: 16px;
  background: rgba(255, 250, 243, 0.7);
}

.api-hint-title {
  margin-bottom: 8px;
  color: var(--text-main);
  font-size: 13px;
  font-weight: 700;
}

.api-steps {
  margin: 0;
  padding-left: 18px;
  color: var(--text-sub);
  font-size: 12px;
  line-height: 1.8;
}

.api-steps li + li {
  margin-top: 4px;
}

@media (max-width: 960px) {
  .login-shell {
    grid-template-columns: 1fr;
    min-height: auto;
  }

  .hero-panel::after {
    display: none;
  }
}

@media (max-width: 640px) {
  .login-page {
    padding: 16px;
  }

  .login-shell {
    border-radius: 24px;
  }

  .hero-panel,
  .form-panel {
    padding: 28px 22px;
  }

  .hero-title {
    font-size: 34px;
  }

  .option-row {
    align-items: flex-start;
    flex-direction: column;
    gap: 10px;
  }
}
</style>





