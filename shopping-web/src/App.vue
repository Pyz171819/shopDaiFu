<template>
  <div id="app">
    <router-view />
  </div>
</template>

<script>
export default {
  name: "AppRoot"
};
</script>

<style>
.el-message {
  min-width: min(520px, calc(100vw - 32px));
  padding: 16px 20px;
  border-radius: 16px;
  border-width: 1px;
  box-shadow: 0 18px 38px rgba(15, 23, 42, 0.18);
  top: 24px !important;
}

.el-message .el-message__content {
  font-size: 15px;
  line-height: 1.6;
  font-weight: 600;
}

.el-message .el-message__icon {
  font-size: 20px;
  margin-right: 10px;
}

@media (max-width: 640px) {
  .el-message {
    min-width: calc(100vw - 20px);
    padding: 14px 16px;
    top: 12px !important;
  }

  .el-message .el-message__content {
    font-size: 14px;
  }
}
</style>
