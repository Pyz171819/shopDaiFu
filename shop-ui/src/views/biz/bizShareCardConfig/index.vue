<template>
  <div class="app-container">
    <el-form :model="queryParams" ref="queryForm" size="small" :inline="true" v-show="showSearch" label-width="68px">
      <el-form-item label="模板标识" prop="tpl">
        <el-input
          v-model="queryParams.tpl"
          placeholder="请输入模板标识"
          clearable
          @keyup.enter.native="handleQuery"
        />
      </el-form-item>
      <el-form-item label="卡片名称" prop="cardName">
        <el-input
          v-model="queryParams.cardName"
          placeholder="请输入卡片名称"
          clearable
          @keyup.enter.native="handleQuery"
        />
      </el-form-item>

      <el-form-item>
        <el-button type="primary" icon="el-icon-search" size="mini" @click="handleQuery">搜索</el-button>
        <el-button icon="el-icon-refresh" size="mini" @click="resetQuery">重置</el-button>
      </el-form-item>
    </el-form>

    <el-row :gutter="10" class="mb8">
      <el-col :span="1.5">
        <el-button
          type="primary"
          plain
          icon="el-icon-plus"
          size="mini"
          @click="handleAdd"
          v-hasPermi="['biz:bizShareCardConfig:add']"
        >新增</el-button>
      </el-col>
      <el-col :span="1.5">
        <el-button
          type="success"
          plain
          icon="el-icon-edit"
          size="mini"
          :disabled="single"
          @click="handleUpdate"
          v-hasPermi="['biz:bizShareCardConfig:edit']"
        >修改</el-button>
      </el-col>
      <el-col :span="1.5">
        <el-button
          type="danger"
          plain
          icon="el-icon-delete"
          size="mini"
          :disabled="multiple"
          @click="handleDelete"
          v-hasPermi="['biz:bizShareCardConfig:remove']"
        >删除</el-button>
      </el-col>
      <el-col :span="1.5">
        <el-button
          type="warning"
          plain
          icon="el-icon-download"
          size="mini"
          @click="handleExport"
          v-hasPermi="['biz:bizShareCardConfig:export']"
        >导出</el-button>
      </el-col>
      <right-toolbar :showSearch.sync="showSearch" @queryTable="getList"></right-toolbar>
    </el-row>

    <el-table v-loading="loading" :data="bizShareCardConfigList" @selection-change="handleSelectionChange">
      <el-table-column type="selection" width="55" align="center" />
      <el-table-column label="模板标识" align="center" prop="tpl" />
      <el-table-column label="卡片名称" align="center" prop="cardName" />
      <el-table-column label="副标题/小字" align="center" prop="shareSubtitle" />
      <el-table-column label="微信分享标题" align="center" prop="shareTitle" />
      <el-table-column label="微信分享描述" align="center" prop="shareDesc" />
      <el-table-column label="固定分享图" align="center" prop="shareImage" width="100">
        <template slot-scope="scope">
          <image-preview :src="scope.row.shareImage" :width="50" :height="50"/>
        </template>
      </el-table-column>
      <el-table-column label="是否使用商品图" align="center" prop="useProductImage" >
        <template slot-scope="scope">
          <el-tag v-if="scope.row.useProductImage === '1'" type="success">是</el-tag>
          <el-tag v-if="scope.row.useProductImage === '0'" type="danger">否</el-tag>
        </template>
      </el-table-column>>
      <el-table-column label="排序值，越大越靠前" align="center" prop="sort" />
      <el-table-column label="操作" align="center" class-name="small-padding fixed-width">
        <template slot-scope="scope">
          <el-button
            size="mini"
            type="text"
            icon="el-icon-edit"
            @click="handleUpdate(scope.row)"
            v-hasPermi="['biz:bizShareCardConfig:edit']"
          >修改</el-button>
          <el-button
            size="mini"
            type="text"
            icon="el-icon-delete"
            @click="handleDelete(scope.row)"
            v-hasPermi="['biz:bizShareCardConfig:remove']"
          >删除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <pagination
      v-show="total>0"
      :total="total"
      :page.sync="queryParams.pageNum"
      :limit.sync="queryParams.pageSize"
      @pagination="getList"
    />

    <!-- 添加或修改分享卡片配置对话框 -->
    <el-dialog :title="title" :visible.sync="open" width="500px" append-to-body>
      <el-form ref="form" :model="form" :rules="rules" label-width="100px">
        <el-row>
          <el-col :span="24">
            <el-form-item label="模板标识" prop="tpl">
              <el-input v-model="form.tpl" placeholder="请输入模板标识" :disabled="title === '修改分享卡片配置'"/>
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="卡片名称" prop="cardName">
              <el-input v-model="form.cardName" placeholder="请输入卡片名称" />
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="副标题/小字" prop="shareSubtitle">
              <el-input v-model="form.shareSubtitle" placeholder="请输入副标题/小字" />
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="微信分享标题" prop="shareTitle">
              <el-input v-model="form.shareTitle" placeholder="请输入微信分享标题" />
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="微信分享描述" prop="shareDesc">
              <el-input v-model="form.shareDesc" type="textarea" placeholder="请输入内容" />
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="固定分享图" prop="shareImage">
              <image-upload v-model="form.shareImage" limit="1"/>
            </el-form-item>
          </el-col>
<!--          加一个单选框（是否使用商品图）-->
          <el-col :span="24">
            <el-form-item label="是否使用商品图" prop="useProductImage" label-width="120px">
              <el-radio-group v-model="form.useProductImage">
                <el-radio label="1">是</el-radio>
                <el-radio label="0">否</el-radio>
              </el-radio-group>
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="排序值" prop="sort">
              <el-input v-model="form.sort" placeholder="请输入排序值，越大越靠前" />
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button type="primary" @click="submitForm">确 定</el-button>
        <el-button @click="cancel">取 消</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import { listBizShareCardConfig, getBizShareCardConfig, delBizShareCardConfig, addBizShareCardConfig, updateBizShareCardConfig } from "@/api/biz/bizShareCardConfig"

export default {
  name: "BizShareCardConfig",
  data() {
    return {
      // 遮罩层
      loading: true,
      // 选中数组
      ids: [],
      // 非单个禁用
      single: true,
      // 非多个禁用
      multiple: true,
      // 显示搜索条件
      showSearch: true,
      // 总条数
      total: 0,
      // 分享卡片配置表格数据
      bizShareCardConfigList: [],
      // 弹出层标题
      title: "",
      // 是否显示弹出层
      open: false,
      // 查询参数
      queryParams: {
        pageNum: 1,
        pageSize: 10,
        tpl: null,
        cardName: null,
        shareSubtitle: null,
        shareTitle: null,
        shareDesc: null,
        shareImage: null,
        useProductImage: null,
        sort: null,
        status: null,
      },
      // 表单参数
      form: {},
      // 表单校验
      rules: {
      }
    }
  },
  created() {
    this.getList()
  },
  methods: {
    /** 查询分享卡片配置列表 */
    getList() {
      this.loading = true
      listBizShareCardConfig(this.queryParams).then(response => {
        this.bizShareCardConfigList = response.rows
        this.total = response.total
        this.loading = false
      })
    },
    // 取消按钮
    cancel() {
      this.open = false
      this.reset()
    },
    // 表单重置
    reset() {
      this.form = {
        id: null,
        tpl: null,
        cardName: null,
        shareSubtitle: null,
        shareTitle: null,
        shareDesc: null,
        shareImage: null,
        useProductImage: '1',
        sort: null,
        status: null,
        createBy: null,
        createTime: null,
        updateBy: null,
        updateTime: null,
        delFlag: null
      }
      this.resetForm("form")
    },
    /** 搜索按钮操作 */
    handleQuery() {
      this.queryParams.pageNum = 1
      this.getList()
    },
    /** 重置按钮操作 */
    resetQuery() {
      this.resetForm("queryForm")
      this.handleQuery()
    },
    // 多选框选中数据
    handleSelectionChange(selection) {
      this.ids = selection.map(item => item.id)
      this.single = selection.length !== 1
      this.multiple = !selection.length
    },
    /** 新增按钮操作 */
    handleAdd() {
      this.reset()
      this.open = true
      this.title = "添加分享卡片配置"
    },
    /** 修改按钮操作 */
    handleUpdate(row) {
      this.reset()
      const id = row.id || this.ids
      getBizShareCardConfig(id).then(response => {
        this.form = response.data
        this.open = true
        this.title = "修改分享卡片配置"
      })
    },
    /** 提交按钮 */
    submitForm() {
      this.$refs["form"].validate(valid => {
        if (valid) {
          if (this.form.id != null) {
            updateBizShareCardConfig(this.form).then(response => {
              this.$modal.msgSuccess("修改成功")
              this.open = false
              this.getList()
            })
          } else {
            addBizShareCardConfig(this.form).then(response => {
              this.$modal.msgSuccess("新增成功")
              this.open = false
              this.getList()
            })
          }
        }
      })
    },
    /** 删除按钮操作 */
    handleDelete(row) {
      const ids = row.id || this.ids
      this.$modal.confirm('是否确认删除此分享卡片配置数据项？').then(function() {
        return delBizShareCardConfig(ids)
      }).then(() => {
        this.getList()
        this.$modal.msgSuccess("删除成功")
      }).catch(() => {})
    },
    /** 导出按钮操作 */
    handleExport() {
      this.download('biz/bizShareCardConfig/export', {
        ...this.queryParams
      }, `bizShareCardConfig_${new Date().getTime()}.xlsx`)
    }
  }
}
</script>
