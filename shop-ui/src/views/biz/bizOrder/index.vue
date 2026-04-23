<template>
  <div class="app-container">
    <el-form :model="queryParams" ref="queryForm" size="small" :inline="true" v-show="showSearch" label-width="68px">
      <!--      商品名称-->
      <el-form-item label="商品名称" prop="productName">
        <el-input v-model="queryParams.items" placeholder="请输入商品名称" clearable />
      </el-form-item>

      <!--      状态查询-->
      <el-form-item label="订单状态" prop="status">
        <el-select v-model="queryParams.status" placeholder="请选择订单状态">
          <el-option label="未支付" value="0"></el-option>
          <el-option label="已支付" value="1"></el-option>
          <el-option label="已过期" value="2"></el-option>
        </el-select>
      </el-form-item>
      <!--      用户id-->
      <el-form-item label="用户ID" prop="createBy">
        <el-input v-model="queryParams.createBy" placeholder="请输入用户ID" clearable />
      </el-form-item>
      <el-form-item>
        <el-button type="primary" icon="el-icon-search" size="mini" @click="handleQuery">搜索</el-button>
        <el-button icon="el-icon-refresh" size="mini" @click="resetQuery">重置</el-button>
      </el-form-item>
    </el-form>

    <el-row :gutter="10" class="mb8">
<!--      <el-col :span="1.5">-->
<!--        <el-button-->
<!--          type="primary"-->
<!--          plain-->
<!--          icon="el-icon-plus"-->
<!--          size="mini"-->
<!--          @click="handleAdd"-->
<!--          v-hasPermi="['biz:bizOrder:add']"-->
<!--        >新增</el-button>-->
<!--      </el-col>-->
<!--      <el-col :span="1.5">-->
<!--        <el-button-->
<!--          type="success"-->
<!--          plain-->
<!--          icon="el-icon-edit"-->
<!--          size="mini"-->
<!--          :disabled="single"-->
<!--          @click="handleUpdate"-->
<!--          v-hasPermi="['biz:bizOrder:edit']"-->
<!--        >修改</el-button>-->
<!--      </el-col>-->
      <el-col :span="1.5">
        <el-button
          type="danger"
          plain
          icon="el-icon-delete"
          size="mini"
          :disabled="multiple"
          @click="handleDelete"
          v-hasPermi="['biz:bizOrder:remove']"
        >删除</el-button>
      </el-col>
<!--      <el-col :span="1.5">-->
<!--        <el-button-->
<!--          type="warning"-->
<!--          plain-->
<!--          icon="el-icon-download"-->
<!--          size="mini"-->
<!--          @click="handleExport"-->
<!--          v-hasPermi="['biz:bizOrder:export']"-->
<!--        >导出</el-button>-->
<!--      </el-col>-->
      <right-toolbar :showSearch.sync="showSearch" @queryTable="getList"></right-toolbar>
    </el-row>

    <el-table v-loading="loading" :data="bizOrderList" @selection-change="handleSelectionChange">
      <el-table-column type="selection" width="55" align="center" />
      <el-table-column label="订单号" align="center" prop="outTradeNo" width="200px"/>
<!--      <el-table-column label="商品ID" align="center" prop="productId" />-->
      <el-table-column label="订单金额" align="center" prop="money" />
      <el-table-column label="订单状态" align="center" prop="status" >
        <template slot-scope="scope">
          <el-tag v-if="scope.row.status === '0'" size="small" type="info">未支付</el-tag>
          <el-tag v-else-if="scope.row.status === '1'" size="small" type="success">已支付</el-tag>
          <el-tag v-else-if="scope.row.status === '2'" size="small" type="danger">已过期</el-tag>
        </template>
      </el-table-column>>
<!--      <el-table-column label="用户OpenID" align="center" prop="openid" />-->
      <el-table-column label="支付方式" align="center" prop="payType" />
<!--      <el-table-column label="订单类型/支付类型标识" align="center" prop="type" />-->
<!--      <el-table-column label="订单名称" align="center" prop="orderName" />-->
<!--      <el-table-column label="页面模板样式" align="center" prop="template" />-->
      <el-table-column label="卡片模板" align="center" prop="tpl" width="100px" />
      <el-table-column label="商品详情" align="center" prop="items" :show-overflow-tooltip="true" width="180px" />
      <el-table-column label="支付时间" align="center" prop="payTime" width="180"/>
      <el-table-column label="订单过期时间" align="center" prop="expireTime" width="180"/>
      <el-table-column label="第三方支付流水号" align="center" prop="transactionId" :show-overflow-tooltip="true" width="180px"/>
      <el-table-column label="支付回调时间" align="center" prop="notifyTime" width="180"/>
      <el-table-column label="用户ID" align="center" prop="createBy" />
      <el-table-column label="操作" align="center" class-name="small-padding fixed-width" width="150px" fixed="right">
        <template slot-scope="scope">
          <el-button
            size="mini"
            type="text"
            icon="el-icon-edit"
            @click="handleUpdate(scope.row)"
            v-hasPermi="['biz:bizOrder:edit']"
          >修改</el-button>
          <el-button
            size="mini"
            type="text"
            icon="el-icon-delete"
            @click="handleDelete(scope.row)"
            v-hasPermi="['biz:bizOrder:remove']"
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

    <!-- 添加或修改订单对话框 -->
    <el-dialog :title="title" :visible.sync="open" width="500px" append-to-body>
      <el-form ref="form" :model="form" :rules="rules" label-width="100px">
        <el-row>
          <el-col :span="24">
            <el-form-item label="商户订单号" prop="outTradeNo">
              <el-input v-model="form.outTradeNo" placeholder="请输入商户订单号" :disabled="true" />
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="订单状态" prop="status">
              <el-select v-model="form.status" placeholder="请选择订单状态">
                <el-option label="未支付（撤回佣金）" value="0"></el-option>
                <el-option label="已支付（发放佣金）" value="1"></el-option>
                <el-option label="已过期（撤回佣金）" value="2"></el-option>
              </el-select>
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
import { listBizOrder, getBizOrder, delBizOrder, addBizOrder, updateBizOrder } from "@/api/biz/bizOrder"
import {parseTime} from "../../../utils/shop";

export default {
  name: "BizOrder",
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
      // 订单表格数据
      bizOrderList: [],
      // 弹出层标题
      title: "",
      // 是否显示弹出层
      open: false,
      // 查询参数
      queryParams: {
        pageNum: 1,
        pageSize: 10,
        outTradeNo: null,
        productId: null,
        money: null,
        status: null,
        openid: null,
        payType: null,
        type: null,
        orderName: null,
        template: null,
        tpl: null,
        items: null,
        payTime: null,
        expireTime: null,
        transactionId: null,
        notifyTime: null,
        orderCreateUserId: null,
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
    parseTime,
    /** 查询订单列表 */
    getList() {
      this.loading = true
      listBizOrder(this.queryParams).then(response => {
        console.log( "response:",response)
        this.bizOrderList = response.rows
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
        outTradeNo: null,
        productId: null,
        money: null,
        status: null,
        openid: null,
        payType: null,
        type: null,
        orderName: null,
        template: null,
        tpl: null,
        items: null,
        payTime: null,
        expireTime: null,
        transactionId: null,
        notifyTime: null,
        orderCreateUserId: null,
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
      this.title = "添加订单"
    },
    /** 修改按钮操作 */
    handleUpdate(row) {
      this.reset()
      const id = row.id || this.ids
      getBizOrder(id).then(response => {
        this.form = response.data
        this.open = true
        this.title = "修改订单"
      })
    },
    /** 提交按钮 */
    submitForm() {
      this.$refs["form"].validate(valid => {
        if (valid) {
          if (this.form.id != null) {
            updateBizOrder(this.form).then(response => {
              this.$modal.msgSuccess("修改成功")
              this.open = false
              this.getList()
            })
          }
          // else {
          //   addBizOrder(this.form).then(response => {
          //     this.$modal.msgSuccess("新增成功")
          //     this.open = false
          //     this.getList()
          //   })
          // }
        }
      })
    },
    /** 删除按钮操作 */
    handleDelete(row) {
      const ids = row.id || this.ids
      this.$modal.confirm('是否确认删除此订单数据项？').then(function() {
        return delBizOrder(ids)
      }).then(() => {
        this.getList()
        this.$modal.msgSuccess("删除成功")
      }).catch(() => {})
    },
    /** 导出按钮操作 */
    handleExport() {
      this.download('biz/order/exportByRY', {
        ...this.queryParams
      }, `bizOrder_${new Date().getTime()}.xlsx`)
    }
  }
}
</script>
