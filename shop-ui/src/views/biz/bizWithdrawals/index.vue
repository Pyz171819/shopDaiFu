<template>
  <div class="app-container">
    <el-form :model="queryParams" ref="queryForm" size="small" :inline="true" v-show="showSearch" label-width="68px">
      <el-form-item label="提现单号" prop="withdrawNo">
        <el-input
          v-model="queryParams.withdrawNo"
          placeholder="请输入提现单号"
          clearable
          @keyup.enter.native="handleQuery"
        />
      </el-form-item>
      <el-form-item label="用户ID" prop="userId">
        <el-input
          v-model="queryParams.userId"
          placeholder="请输入用户ID"
          clearable
          @keyup.enter.native="handleQuery"
        />
      </el-form-item>
      <el-form-item label="审核状态" prop="status">
        <el-select v-model="queryParams.status" placeholder="请选择审核状态" clearable size="small">
          <el-option label="待审核" value="0"></el-option>
          <el-option label="已打款" value="1"></el-option>
          <el-option label="已驳回" value="2"></el-option>
        </el-select>
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
<!--          v-hasPermi="['biz:bizWithdrawals:add']"-->
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
<!--          v-hasPermi="['biz:bizWithdrawals:edit']"-->
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
          v-hasPermi="['biz:bizWithdrawals:remove']"
        >删除</el-button>
      </el-col>
      <el-col :span="1.5">
        <el-button
          type="warning"
          plain
          icon="el-icon-download"
          size="mini"
          @click="handleExport"
          v-hasPermi="['biz:bizWithdrawals:export']"
        >导出</el-button>
      </el-col>
      <right-toolbar :showSearch.sync="showSearch" @queryTable="getList"></right-toolbar>
    </el-row>

    <el-table v-loading="loading" :data="bizWithdrawalsList" @selection-change="handleSelectionChange">
      <el-table-column type="selection" width="55" align="center" />
      <el-table-column label="提现单号" align="center" prop="withdrawNo" width="150px"/>
      <el-table-column label="用户ID" align="center" prop="userId" />
      <el-table-column label="用户昵称" align="center" prop="userId" />
      <el-table-column label="提现金额" align="center" prop="money" />
      <el-table-column label="收款码图片" align="center" prop="qrcode" width="100">
        <template slot-scope="scope">
          <image-preview :src="scope.row.qrcode" :width="50" :height="50"/>
        </template>
      </el-table-column>
      <el-table-column label="状态" align="center" prop="status" >
        <template slot-scope="scope">
          <el-tag v-if="scope.row.status == 0" type="info"  >待审核</el-tag>
          <el-tag v-if="scope.row.status == 1" type="success">已打款</el-tag>
          <el-tag v-if="scope.row.status == 2" type="danger">已驳回</el-tag>
        </template>
      </el-table-column>>
      <el-table-column label="驳回原因" align="center" prop="reason" show-overflow-tooltip/>
      <el-table-column label="审核人" align="center" prop="auditBy" />
      <el-table-column label="审核时间" align="center" prop="auditTime" width="180">
        <template slot-scope="scope">
          <span>{{ parseTime(scope.row.auditTime, '{y}-{m}-{d} ') }}</span>
        </template>
      </el-table-column>
      <el-table-column label="打款人" align="center" prop="payBy" />
      <el-table-column label="打款时间" align="center" prop="payTime" width="180">
        <template slot-scope="scope">
          <span>{{ parseTime(scope.row.payTime, '{y}-{m}-{d}') }}</span>
        </template>
      </el-table-column>
      <el-table-column label="操作" align="center" class-name="small-padding fixed-width" width="150px" >
        <template slot-scope="scope">
          <el-button
            size="mini"
            type="text"
            icon="el-icon-edit"
            @click="handleUpdate(scope.row)"
            v-hasPermi="['biz:bizWithdrawals:edit']"
          >修改</el-button>
          <el-button
            size="mini"
            type="text"
            icon="el-icon-edit"
            @click="handleAudit(scope.row)"
            v-if="scope.row.status === '0'"
            v-hasPermi="['biz:bizWithdrawals:edit']"
          >审核</el-button>
          <el-button
            size="mini"
            type="text"
            icon="el-icon-delete"
            @click="handleDelete(scope.row)"
            v-hasPermi="['biz:bizWithdrawals:remove']"
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

    <!-- 添加或修改提现申请对话框 -->
    <el-dialog :title="title" :visible.sync="open" width="500px" append-to-body>
      <el-form ref="form" :model="form" :rules="rules" label-width="100px">
        <el-row>

<!--          <el-col :span="24">-->
<!--            <el-form-item label="用户ID" prop="userId">-->
<!--              <el-input v-model="form.userId" placeholder="请输入用户ID" />-->
<!--            </el-form-item>-->
<!--          </el-col>-->
          <el-col :span="24">
            <el-form-item label="提现金额" prop="money">
              <el-input v-model="form.money" placeholder="请输入提现金额" />
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="收款码图片" prop="qrcode">
              <image-upload v-model="form.qrcode"/>
            </el-form-item>
          </el-col>
<!--          <el-col :span="24">-->
<!--            <el-form-item label="驳回原因" prop="reason">-->
<!--              <el-input v-model="form.reason" placeholder="请输入驳回原因" />-->
<!--            </el-form-item>-->
<!--          </el-col>-->
<!--          <el-col :span="24">-->
<!--            <el-form-item label="审核人" prop="auditBy">-->
<!--              <el-input v-model="form.auditBy" placeholder="请输入审核人" />-->
<!--            </el-form-item>-->
<!--          </el-col>-->
<!--          <el-col :span="24">-->
<!--            <el-form-item label="审核时间" prop="auditTime">-->
<!--              <el-date-picker clearable-->
<!--                v-model="form.auditTime"-->
<!--                type="date"-->
<!--                value-format="yyyy-MM-dd"-->
<!--                placeholder="请选择审核时间">-->
<!--              </el-date-picker>-->
<!--            </el-form-item>-->
<!--          </el-col>-->
<!--          <el-col :span="24">-->
<!--            <el-form-item label="打款人" prop="payBy">-->
<!--              <el-input v-model="form.payBy" placeholder="请输入打款人" />-->
<!--            </el-form-item>-->
<!--          </el-col>-->
<!--          <el-col :span="24">-->
<!--            <el-form-item label="打款时间" prop="payTime">-->
<!--              <el-date-picker clearable-->
<!--                v-model="form.payTime"-->
<!--                type="date"-->
<!--                value-format="yyyy-MM-dd"-->
<!--                placeholder="请选择打款时间">-->
<!--              </el-date-picker>-->
<!--            </el-form-item>-->
<!--          </el-col>-->
<!--          <el-col :span="24">-->
<!--            <el-form-item label="删除标志" prop="delFlag">-->
<!--              <el-input v-model="form.delFlag" placeholder="请输入删除标志" />-->
<!--            </el-form-item>-->
<!--          </el-col>-->
        </el-row>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button type="primary" @click="submitForm">确 定</el-button>
        <el-button @click="cancel">取 消</el-button>
      </div>
    </el-dialog>

    <el-dialog :title="auditTitle" :visible.sync="auditOpen" width="500px" append-to-body>
      <el-form ref="auditForm" :model="auditForm" :rules="auditRules" label-width="100px">
        <el-form-item label="提现单号">
          <span>{{ auditForm.withdrawNo || '-' }}</span>
        </el-form-item>

        <el-form-item label="提现金额">
          <span>{{ auditForm.money }}</span>
        </el-form-item>

        <el-form-item label="审核结果" prop="status">
          <el-radio-group v-model="auditForm.status">
            <el-radio label="1">通过并打款</el-radio>
            <el-radio label="2">驳回</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="收款二维码" prop="qrcode" v-if="auditForm.status === '1'">
          <image-upload v-model="auditForm.qrcode" limit="1" :disabled="true"/>
        </el-form-item>

        <el-form-item label="驳回原因" prop="reason" v-if="auditForm.status === '2'">
          <el-input
            v-model="auditForm.reason"
            type="textarea"
            :rows="3"
            placeholder="请输入驳回原因"
          />
        </el-form-item>

      </el-form>

      <div slot="footer" class="dialog-footer">
        <el-button type="primary" @click="submitAudit">确 定</el-button>
        <el-button @click="cancelAudit">取 消</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import {
  listBizWithdrawals, getBizWithdrawals, delBizWithdrawals, addBizWithdrawals, updateBizWithdrawals,
  auditBizWithdrawals
} from "@/api/biz/bizWithdrawals"
import {parseTime} from "../../../utils/shop";

export default {
  name: "BizWithdrawals",
  data() {
    return {
      // 审核弹窗
      auditOpen: false,
      auditTitle: "",
      // 审核表单
      auditForm: {
        id: null,
        userId: null,
        withdrawNo: "",
        money: null,
        status: '1', // 1通过 3驳回
        reason: ""
      },
      // 审核校验
      auditRules: {
        status: [
          { required: true, message: "请选择审核结果", trigger: "change" }
        ],
        reason: [
          { validator: (rule, value, callback) => {
              if (this.auditForm.status === '3' && (!value || !value.trim())) {
                callback(new Error("驳回时请输入驳回原因"))
              } else {
                callback()
              }
            }, trigger: "blur"
          }
        ]
      },
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
      // 提现申请表格数据
      bizWithdrawalsList: [],
      // 弹出层标题
      title: "",
      // 是否显示弹出层
      open: false,
      // 查询参数
      queryParams: {
        pageNum: 1,
        pageSize: 10,
        withdrawNo: null,
        userId: null,
        money: null,
        qrcode: null,
        status: null,
        reason: null,
        auditBy: null,
        auditTime: null,
        payBy: null,
        payTime: null,
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
    resetAuditForm() {
      this.auditForm = {
        id: null,
        userId: null,
        withdrawNo: "",
        money: null,
        status: '1',
        reason: "",
        auditRemark: ""
      }

      this.$nextTick(() => {
        if (this.$refs.auditForm) {
          this.$refs.auditForm.resetFields()
        }
      })
    },
    parseTime,
    /** 查询提现申请列表 */
    getList() {
      this.loading = true
      listBizWithdrawals(this.queryParams).then(response => {
        this.bizWithdrawalsList = response.rows
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
        withdrawNo: null,
        userId: null,
        money: null,
        qrcode: null,
        status: null,
        reason: null,
        auditBy: null,
        auditTime: null,
        payBy: null,
        payTime: null,
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
      this.title = "添加提现申请"
    },
    /** 修改按钮操作 */
    handleUpdate(row) {
      this.reset()
      const id = row.id || this.ids
      getBizWithdrawals(id).then(response => {
        this.form = response.data
        this.open = true
        this.title = "修改提现申请"
      })
    },
    handleAudit(row) {
      this.resetAuditForm()

      this.auditForm.id = row.id
      this.auditForm.userId = row.userId
      this.auditForm.withdrawNo = row.withdrawNo
      this.auditForm.money = row.money
      this.auditForm.status = '1' // 默认选中通过
      this.auditForm.reason = ''
      this.auditForm.qrcode = row.qrcode

      this.auditOpen = true
      this.auditTitle = "审核提现申请"
    },
    submitAudit() {
      this.$refs["auditForm"].validate(valid => {
        if (!valid) {
          return
        }

        const data = {
          id: this.auditForm.id,
          status: this.auditForm.status,
          reason: this.auditForm.status === '2' ? this.auditForm.reason : ""
        }

        console.log('data:', data)
        auditBizWithdrawals(data).then(response => {
            this.$modal.msgSuccess("审核成功")
            this.auditOpen = false
            this.getList()
        }).catch(() => {})
      })
    },
    cancelAudit() {
      this.auditOpen = false
      this.resetAuditForm()
    },
    /** 提交按钮 */
    submitForm() {
      this.$refs["form"].validate(valid => {
        if (valid) {
          if (this.form.id != null) {
            updateBizWithdrawals(this.form).then(response => {
              this.$modal.msgSuccess("修改成功")
              this.open = false
              this.getList()
            })
          } else {
            addBizWithdrawals(this.form).then(response => {
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
      this.$modal.confirm('是否确认删除提现申请编号为"' + ids + '"的数据项？').then(function() {
        return delBizWithdrawals(ids)
      }).then(() => {
        this.getList()
        this.$modal.msgSuccess("删除成功")
      }).catch(() => {})
    },
    /** 导出按钮操作 */
    handleExport() {
      this.download('biz/bizWithdrawals/export', {
        ...this.queryParams
      }, `bizWithdrawals_${new Date().getTime()}.xlsx`)
    }
  }
}
</script>
