<template>
  <div class="app-container">
    <el-form :model="queryParams" ref="queryForm" size="small" :inline="true" v-show="showSearch" label-width="68px">
      <el-form-item label="商品名称" prop="name">
        <el-input
          v-model="queryParams.name"
          placeholder="请输入商品名称"
          clearable
          @keyup.enter.native="handleQuery"
        />
      </el-form-item>
      <el-form-item label="店铺/标签名称" prop="shopName" label-width="100px">
        <el-input
          v-model="queryParams.shopName"
          placeholder="请输入店铺/标签名称"
          clearable
          @keyup.enter.native="handleQuery"
        />
      </el-form-item>
<!--      根据审核状态搜索，不是字典值，是固定值-->
      <el-form-item label="审核状态" prop="auditStatus">
        <el-select
          v-model="queryParams.auditStatus"
          placeholder="请选择审核状态"
          clearable
          style="width: 160px"
        >
          <el-option label="待审核" value="0" />
          <el-option label="审核通过" value="1" />
          <el-option label="审核未通过" value="2" />
        </el-select>
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
          v-hasPermi="['biz:products:edit']"
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
          v-hasPermi="['biz:products:remove']"
        >删除</el-button>
      </el-col>
      <el-col :span="1.5">
        <el-button
          type="warning"
          plain
          icon="el-icon-download"
          size="mini"
          @click="handleExport"
          v-hasPermi="['biz:products:export']"
        >导出</el-button>
      </el-col>
      <right-toolbar :showSearch.sync="showSearch" @queryTable="getList"></right-toolbar>
    </el-row>

    <el-table v-loading="loading" :data="productsList" @selection-change="handleSelectionChange">
      <el-table-column type="selection" width="55" align="center" />
<!--      <el-table-column label="商品ID" align="center" prop="id" />-->
      <el-table-column label="分类" align="center" prop="categoryName" />
      <el-table-column label="所属用户" align="center" prop="userName" />
      <el-table-column label="商品名称" align="center" prop="name" />
      <el-table-column label="商品价格" align="center" prop="price" />
      <el-table-column label="商品图片地址" align="center" prop="image" width="100">
        <template slot-scope="scope">
          <image-preview :src="scope.row.image" :width="50" :height="50"/>
        </template>
      </el-table-column>
      <el-table-column label="店铺/标签名称" align="center" prop="shopName" />
      <el-table-column label="审核状态" align="center" prop="auditStatus" >
        <template slot-scope="scope">
          <el-tag v-if="scope.row.auditStatus === '0'" type="warning">待审核</el-tag>
          <el-tag v-if="scope.row.auditStatus === '1'" type="success">审核通过</el-tag>
          <el-tag v-if="scope.row.auditStatus === '2'" type="danger">审核未通过</el-tag>
        </template>
      </el-table-column>>
      <el-table-column label="商品状态" align="center" prop="status" >
        <template slot-scope="scope">
          <el-tag v-if="scope.row.status === '0'" type="info">下架</el-tag>
          <el-tag v-if="scope.row.status === '1'" type="success">正常</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="创建时间" align="center" prop="createTime" width="180px"/>

      <el-table-column label="操作" align="center" class-name="small-padding fixed-width">
        <template slot-scope="scope">
          <el-button
            size="mini"
            type="text"
            icon="el-icon-edit"
            @click="handleUpdate(scope.row)"
            v-hasPermi="['biz:products:edit']"
          >修改</el-button>

          <el-dropdown
            v-if="scope.row.auditStatus === '0'"
            trigger="hover"
            @command="(command) => handleAuditCommand(command, scope.row)"
            v-hasPermi="['biz:products:audit']"
          >
            <span
              class="el-dropdown-link"
              style="margin: 0 10px; font-size: 12px; color: #e6a23c; cursor: pointer;"
            >
              <i class="el-icon-s-check"></i> 审核
            </span>
            <el-dropdown-menu slot="dropdown">
              <el-dropdown-item command="pass">
                <i class="el-icon-circle-check" style="color: #67c23a;"></i>
                通过
              </el-dropdown-item>
              <el-dropdown-item command="reject">
                <i class="el-icon-circle-close" style="color: #f56c6c;"></i>
                不通过
              </el-dropdown-item>
            </el-dropdown-menu>
          </el-dropdown>
          <el-button
            size="mini"
            type="text"
            icon="el-icon-delete"
            @click="handleDelete(scope.row)"
            v-hasPermi="['biz:products:remove']"
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

    <!-- 添加或修改商品对话框 -->
    <el-dialog :title="title" :visible.sync="open" width="500px" append-to-body>
      <el-form ref="form" :model="form" :rules="rules" label-width="100px">
        <el-row>
          <el-col :span="24">
            <el-form-item label="商品名称" prop="name">
              <el-input v-model="form.name" placeholder="请输入商品名称" />
            </el-form-item>
          </el-col>
<!--          商品分类下拉框-->
          <el-col :span="24">
            <el-form-item label="商品分类" prop="categoryId">
              <el-select v-model="form.categoryId" placeholder="请选择商品分类">
                <el-option
                  v-for="item in categoryList"
                  :key="item.id"
                  :label="item.name"
                  :value="item.id"
                />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="商品价格" prop="price">
              <el-input v-model="form.price" placeholder="请输入商品价格" />
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="商品图片地址" prop="image">
              <image-upload v-model="form.image" limit="1"/>
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="店铺/标签名称" prop="shopName">
              <el-input v-model="form.shopName" placeholder="请输入店铺/标签名称" />
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
import { listProducts, getProducts, delProducts, addProducts, updateProducts, auditProduct } from "@/api/biz/products"
import { listCategories } from "@/api/biz/categories"

export default {
  name: "Products",
  data() {
    return {
      categoryList:[],
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
      // 商品表格数据
      productsList: [],
      // 弹出层标题
      title: "",
      // 是否显示弹出层
      open: false,
      // 查询参数
      queryParams: {
        pageNum: 1,
        pageSize: 10,
        categoryId: null,
        userId: null,
        name: null,
        shopName: null,
        auditStatus: null,
        status: null
      },
      // 表单参数
      form: {},
      // 表单校验
      rules: {
        name: [
          { required: true, message: "商品名称不能为空", trigger: "blur" }
        ],
      }
    }
  },
  created() {
    this.getList()
  },
  methods: {

    handleAuditCommand(command, row) {
      if (command === "pass") {
        this.handleAuditPass(row);
      } else if (command === "reject") {
        this.handleAuditReject(row);
      }
    },

    handleAuditPass(row) {
      this.$modal.confirm(`确认审核通过商品“${row.name}”吗？`)
        .then(() => {
          return auditProduct({
            id: row.id,
            auditStatus: "1"
          });
        })
        .then(() => {
          this.$modal.msgSuccess("审核通过成功");
          this.getList();
        })
        .catch(() => {});
    },

    handleAuditReject(row) {
      this.$modal.confirm(`确认审核不通过商品“${row.name}”吗？`)
        .then(() => {
          return auditProduct({
            id: row.id,
            auditStatus: "2"
          });
        })
        .then(() => {
          this.$modal.msgSuccess("审核不通过成功");
          this.getList();
        })
        .catch(() => {});
    },


    /** 查询商品分类 */
    getCategoryList() {
      listCategories().then(response => {
        this.categoryList = response.rows
      })
    },
    /** 查询商品列表 */
    getList() {
      this.loading = true
      listProducts(this.queryParams).then(response => {
        this.productsList = response.rows
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
        categoryId: null,
        userId: null,
        name: null,
        price: null,
        image: null,
        shopName: null,
        auditStatus: null,
        sort: null,
        status: null,
        createBy: null,
        updateBy: null,
        createTime: null,
        updateTime: null
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
      this.getCategoryList()
      this.open = true
      this.title = "添加商品"
    },
    /** 修改按钮操作 */
    handleUpdate(row) {
      this.reset()
      this.getCategoryList()
      const id = row.id || this.ids
      getProducts(id).then(response => {
        this.form = response.data
        this.open = true
        this.title = "修改商品"
      })
    },
    /** 提交按钮 */
    submitForm() {
      this.$refs["form"].validate(valid => {
        if (valid) {
          if (this.form.id != null) {
            updateProducts(this.form).then(response => {
              this.$modal.msgSuccess("修改成功")
              this.open = false
              this.getList()
            })
          } else {
            addProducts(this.form).then(response => {
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
      this.$modal.confirm('是否确认删除商品名称为"' + row.name + '"的数据项？').then(function() {
        return delProducts(ids)
      }).then(() => {
        this.getList()
        this.$modal.msgSuccess("删除成功")
      }).catch(() => {})
    },
    /** 导出按钮操作 */
    handleExport() {
      this.download('biz/products/export', {
        ...this.queryParams
      }, `products_${new Date().getTime()}.xlsx`)
    }
  }
}
</script>
