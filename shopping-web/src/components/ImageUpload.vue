<template>
  <div class="image-upload">
    <el-upload
        v-if="!currentValue || !disabled"
        class="image-uploader"
        :action="uploadUrl"
        :headers="uploadHeaders"
        :show-file-list="false"
        :before-upload="handleBeforeUpload"
        :on-success="handleUploadSuccess"
        :on-error="handleUploadError"
        :disabled="disabled"
        accept="image/*"
    >
      <div v-if="currentValue" class="image-preview">
        <img :src="getImageUrl(currentValue)" class="preview-image" />
        <div v-if="!disabled" class="image-actions" @click.stop>
          <i class="el-icon-delete action-icon" @click="handleRemove"></i>
        </div>
      </div>

      <div v-else class="upload-placeholder">
        <i class="el-icon-plus uploader-icon"></i>
        <div class="uploader-text">{{ placeholder }}</div>
      </div>
    </el-upload>

    <!-- 禁用状态且有图片时，只显示图片 -->
    <div v-if="currentValue && disabled" class="image-preview-only">
      <img :src="getImageUrl(currentValue)" class="preview-image" />
    </div>

    <div v-if="showTip" class="upload-tip">
      请上传
      <span v-if="fileType && fileType.length">{{ fileType.join('/') }}</span>
      格式文件，且大小不超过 {{ fileSize }}MB
    </div>

    <el-dialog :visible.sync="dialogVisible" append-to-body width="520px">
      <img :src="previewUrl" style="display: block; width: 100%;" />
    </el-dialog>
  </div>
</template>

<script>
import { getToken } from "@/utils/auth";

export default {
  name: "ImageUpload",
  props: {
    value: {
      type: String,
      default: ""
    },
    // 上传地址前缀，独立 Vue 前端一般走代理
    baseUrl: {
      type: String,
      default: "/api"
    },
    // 上传接口
    uploadApi: {
      type: String,
      default: "/common/upload"
    },
    // 图片最大大小，单位 MB
    fileSize: {
      type: Number,
      default: 5
    },
    // 允许的后缀
    fileType: {
      type: Array,
      default: () => ["png", "jpg", "jpeg", "gif", "webp"]
    },
    placeholder: {
      type: String,
      default: "点击上传"
    },
    disabled: {
      type: Boolean,
      default: false
    },
    showTip: {
      type: Boolean,
      default: true
    }
  },
  data() {
    return {
      dialogVisible: false,
      previewUrl: ""
    };
  },
  computed: {
    uploadUrl() {
      return `${this.baseUrl}${this.uploadApi}`;
    },
    uploadHeaders() {
      const token = getToken();
      return token
          ? {
            Authorization: "Bearer " + token
          }
          : {};
    },
    currentValue() {
      return this.value || "";
    }
  },
  methods: {
    getImageUrl(path) {
      if (!path) return "";
      if (path.startsWith("http://") || path.startsWith("https://")) {
        return path;
      }
      // 若依返回相对路径时，统一拼接代理前缀
      return `${this.baseUrl}${path}`;
    },

    handleBeforeUpload(file) {
      const fileName = file.name || "";
      const suffix = fileName.includes(".")
          ? fileName.split(".").pop().toLowerCase()
          : "";
      const isValidType =
          !this.fileType.length || this.fileType.includes(suffix);
      const isValidSize = file.size / 1024 / 1024 < this.fileSize;

      if (!isValidType) {
        this.$message.error(
            `上传图片格式不正确，请上传 ${this.fileType.join("/")} 格式文件`
        );
        return false;
      }

      if (!isValidSize) {
        this.$message.error(`上传图片大小不能超过 ${this.fileSize}MB`);
        return false;
      }

      return true;
    },

    handleUploadSuccess(res) {
      if (res.code !== 200) {
        this.$message.error(res.msg || "上传失败");
        return;
      }

      // 兼容若依常见返回结构
      const filePath = res.fileName ;

      if (!filePath) {
        this.$message.error("上传成功，但未获取到文件路径");
        return;
      }

      this.$emit("input", filePath);
      this.$emit("change", filePath);
      this.$message.success("上传成功");
    },

    handleUploadError() {
      this.$message.error("上传失败，请稍后重试");
    },

    handleRemove() {
      this.$emit("input", "");
      this.$emit("change", "");
    },

    handlePreview() {
      this.previewUrl = this.getImageUrl(this.currentValue);
      this.dialogVisible = true;
    }
  }
};
</script>

<style scoped>
.image-upload {
  display: inline-block;
}

.image-uploader {
  display: inline-block;
}

.upload-placeholder,
.image-preview {
  width: 110px;
  height: 110px;
  border: 1px dashed #d9d9d9;
  border-radius: 6px;
  overflow: hidden;
  position: relative;
  background: #fff;
}

.upload-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #8c939d;
}

.uploader-icon {
  font-size: 28px;
  margin-bottom: 6px;
}

.uploader-text {
  font-size: 12px;
}

.preview-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.image-actions {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 18px;
  opacity: 0;
  transition: opacity 0.2s;
}

.image-preview:hover .image-actions {
  opacity: 1;
}

.action-icon {
  color: #fff;
  font-size: 30px;
  cursor: pointer;
}

.upload-tip {
  margin-top: 8px;
  font-size: 12px;
  color: #909399;
  line-height: 1.5;
}
</style>