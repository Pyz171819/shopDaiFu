<template>
  <div class="app-container home" v-loading="loading">
    <div class="dashboard-header">
      <h2>运营大屏</h2>
      <div class="brand-badge">
        <span class="brand-dot" />
        <span>数据总览</span>
      </div>
    </div>

    <el-row :gutter="20" class="stats-row">
      <el-col v-for="card in statCards" :key="card.title" :xs="24" :sm="12" :lg="6">
        <div class="stat-card" :class="card.theme">
          <div class="stat-card__content">
            <p class="stat-card__title">{{ card.title }}</p>
            <h3 class="stat-card__value">{{ card.value }}</h3>
            <p class="stat-card__extra">{{ card.extra }}</p>
          </div>
          <div class="stat-card__decor">
            <i :class="card.icon" />
          </div>
        </div>
      </el-col>
    </el-row>

    <el-row :gutter="20" class="chart-row">
      <el-col :xs="24" :lg="16">
        <div class="panel chart-panel">
          <div class="panel__title panel__title--primary">
            <i class="el-icon-data-line" />
            <span>近7日营收走势</span>
          </div>
          <div ref="revenueChart" class="chart chart--line" />
        </div>
      </el-col>
      <el-col :xs="24" :lg="8">
        <div class="panel chart-panel">
          <div class="panel__title panel__title--primary">
            <i class="el-icon-pie-chart" />
            <span>订单转化概览</span>
          </div>
          <div ref="orderChart" class="chart chart--pie" />
        </div>
      </el-col>
    </el-row>

    <el-row :gutter="20" class="list-row">
      <el-col :xs="24" :lg="12">
        <div class="panel list-panel">
          <div class="panel__title panel__title--danger">
            <i class="el-icon-collection-tag" />
            <span>热销商品 TOP 5</span>
          </div>
          <div v-if="topProducts.length" class="product-list">
            <div v-for="(item, index) in topProducts" :key="item.name" class="product-item">
              <div class="product-item__rank">#{{ index + 1 }}</div>
              <div class="product-item__info">
                <div class="product-item__name">{{ item.name }}</div>
                <div class="product-item__meta">销量: {{ item.sales }} 单</div>
              </div>
              <div class="product-item__amount">¥{{ formatMoney(item.amount) }}</div>
            </div>
          </div>
          <el-empty v-else :image-size="80" description="暂无数据" />
        </div>
      </el-col>
      <el-col :xs="24" :lg="12">
        <div class="panel list-panel">
          <div class="panel__title panel__title--info">
            <i class="el-icon-user-solid" />
            <span>最新注册用户</span>
          </div>
          <div v-if="latestUsers.length" class="user-list">
            <div v-for="user in latestUsers" :key="user.name + user.time" class="user-item">
              <div class="user-item__avatar">
                <i class="el-icon-user" />
              </div>
              <div class="user-item__info">
                <div class="user-item__name">{{ user.name }}</div>
                <div class="user-item__meta">{{ user.ip || '-' }}</div>
              </div>
              <div class="user-item__time">{{ user.time || '-' }}</div>
            </div>
          </div>
          <el-empty v-else :image-size="80" description="暂无数据" />
        </div>
      </el-col>
    </el-row>
  </div>
</template>

<script>
import * as echarts from "echarts"
import { getDashboardOverview } from "@/api/biz/dashboard"

require("echarts/theme/macarons")

const defaultOverview = () => ({
  todayIncome: 0,
  yesterdayIncome: 0,
  todayOrders: 0,
  conversionRate: 0,
  totalUsers: 0,
  todayNewUsers: 0,
  pendingOrders: 0,
  paidOrders: 0,
  unpaidOrders: 0,
  revenueDates: [],
  revenueValues: [],
  topProducts: [],
  latestUsers: []
})

export default {
  name: "Index",
  data() {
    return {
      loading: false,
      revenueChart: null,
      orderChart: null,
      overview: defaultOverview()
    }
  },
  computed: {
    statCards() {
      return [
        {
          title: "今日收入（元）",
          value: `¥${this.formatMoney(this.overview.todayIncome)}`,
          extra: `昨日: ¥${this.formatMoney(this.overview.yesterdayIncome)}`,
          theme: "theme-blue",
          icon: "el-icon-money"
        },
        {
          title: "今日订单（笔）",
          value: this.formatCount(this.overview.todayOrders),
          extra: `成交率: ${this.formatRate(this.overview.conversionRate)}`,
          theme: "theme-green",
          icon: "el-icon-shopping-cart-2"
        },
        {
          title: "总用户数（人）",
          value: this.formatCount(this.overview.totalUsers),
          extra: `今日新增: +${this.formatCount(this.overview.todayNewUsers)}`,
          theme: "theme-cyan",
          icon: "el-icon-user"
        },
        {
          title: "待支付订单",
          value: this.formatCount(this.overview.pendingOrders),
          extra: "待处理订单",
          theme: "theme-gold",
          icon: "el-icon-time"
        }
      ]
    },
    topProducts() {
      return this.overview.topProducts || []
    },
    latestUsers() {
      return this.overview.latestUsers || []
    }
  },
  mounted() {
    this.$nextTick(() => {
      this.initCharts()
      this.fetchDashboardData()
      window.addEventListener("resize", this.handleResize)
    })
  },
  beforeDestroy() {
    window.removeEventListener("resize", this.handleResize)
    this.disposeCharts()
  },
  methods: {
    initCharts() {
      if (this.$refs.revenueChart && !this.revenueChart) {
        this.revenueChart = echarts.init(this.$refs.revenueChart, "macarons")
      }
      if (this.$refs.orderChart && !this.orderChart) {
        this.orderChart = echarts.init(this.$refs.orderChart, "macarons")
      }
      this.renderCharts()
    },
    fetchDashboardData() {
      this.loading = true
      getDashboardOverview().then(response => {
        this.overview = Object.assign(defaultOverview(), response.data || {})
        this.renderCharts()
      }).finally(() => {
        this.loading = false
      })
    },
    renderCharts() {
      this.renderRevenueChart()
      this.renderOrderChart()
    },
    renderRevenueChart() {
      if (!this.revenueChart) {
        return
      }
      const revenueDates = this.overview.revenueDates && this.overview.revenueDates.length
        ? this.overview.revenueDates
        : this.buildDefaultRevenueDates()
      const revenueValues = this.overview.revenueValues && this.overview.revenueValues.length
        ? this.overview.revenueValues.map(item => Number(item || 0))
        : [0, 0, 0, 0, 0, 0, 0]
      const axisMax = this.getRevenueAxisMax(revenueValues)

      this.revenueChart.setOption({
        tooltip: {
          trigger: "axis",
          formatter: params => {
            const current = params && params[0] ? params[0] : { axisValue: "", data: 0 }
            return `${current.axisValue}<br />营收: ¥${this.formatMoney(current.data)}`
          }
        },
        grid: {
          top: 30,
          right: 20,
          bottom: 30,
          left: 45
        },
        xAxis: {
          type: "category",
          boundaryGap: false,
          data: revenueDates,
          axisLine: {
            lineStyle: {
              color: "#d9e2f2"
            }
          },
          axisLabel: {
            color: "#8d95aa"
          }
        },
        yAxis: {
          type: "value",
          min: 0,
          max: axisMax,
          interval: axisMax / 5,
          axisLine: {
            show: false
          },
          axisTick: {
            show: false
          },
          splitLine: {
            lineStyle: {
              color: "#eef2fb"
            }
          },
          axisLabel: {
            color: "#8d95aa",
            formatter: value => this.formatAxisValue(value)
          }
        },
        series: [
          {
            name: "营收",
            type: "line",
            smooth: true,
            symbol: "circle",
            symbolSize: 7,
            lineStyle: {
              width: 3,
              color: "#31d0a0"
            },
            itemStyle: {
              color: "#31d0a0",
              borderColor: "#ffffff",
              borderWidth: 2
            },
            areaStyle: {
              color: "rgba(49, 208, 160, 0.12)"
            },
            data: revenueValues
          }
        ]
      })
    },
    renderOrderChart() {
      if (!this.orderChart) {
        return
      }
      const paidOrders = Number(this.overview.paidOrders || 0)
      const unpaidOrders = Number(this.overview.unpaidOrders || 0)
      const hasOrderData = paidOrders > 0 || unpaidOrders > 0
      const seriesData = hasOrderData
        ? [
          { value: paidOrders, name: "已支付", itemStyle: { color: "#31d0a0" } },
          { value: unpaidOrders, name: "待支付", itemStyle: { color: "#ffc63b" } }
        ]
        : [
          { value: 1, name: "暂无订单", itemStyle: { color: "#dfe6f5" } }
        ]

      this.orderChart.setOption({
        tooltip: {
          trigger: "item",
          formatter: hasOrderData ? "{b}: {c} ({d}%)" : "{b}"
        },
        legend: hasOrderData ? {
          bottom: 10,
          left: "center",
          itemWidth: 18,
          itemHeight: 10,
          textStyle: {
            color: "#6e768b"
          },
          data: ["已支付", "待支付"]
        } : null,
        series: [
          {
            name: "订单状态",
            type: "pie",
            radius: ["48%", "72%"],
            center: ["50%", "45%"],
            avoidLabelOverlap: false,
            itemStyle: {
              borderColor: "#ffffff",
              borderWidth: 3
            },
            label: {
              show: false
            },
            emphasis: {
              scale: false
            },
            data: seriesData
          }
        ]
      })
    },
    handleResize() {
      this.revenueChart && this.revenueChart.resize()
      this.orderChart && this.orderChart.resize()
    },
    disposeCharts() {
      if (this.revenueChart) {
        this.revenueChart.dispose()
        this.revenueChart = null
      }
      if (this.orderChart) {
        this.orderChart.dispose()
        this.orderChart = null
      }
    },
    formatMoney(value) {
      return Number(value || 0).toFixed(2)
    },
    formatCount(value) {
      return String(Number(value || 0))
    },
    formatRate(value) {
      return `${Number(value || 0).toFixed(2)}%`
    },
    formatAxisValue(value) {
      const numericValue = Number(value || 0)
      if (Number.isInteger(numericValue)) {
        return `${numericValue}`
      }
      return numericValue.toFixed(1)
    },
    getRevenueAxisMax(values) {
      const maxValue = Math.max(...values, 0)
      if (maxValue <= 0) {
        return 1
      }
      return Math.ceil((maxValue * 1.2) / 5) * 5 || 1
    },
    buildDefaultRevenueDates() {
      const dates = []
      const today = new Date()
      for (let index = 6; index >= 0; index -= 1) {
        const current = new Date(today)
        current.setDate(today.getDate() - index)
        const month = `${current.getMonth() + 1}`.padStart(2, "0")
        const day = `${current.getDate()}`.padStart(2, "0")
        dates.push(`${month}-${day}`)
      }
      return dates
    }
  }
}
</script>

<style scoped lang="scss">
.home {
  min-height: calc(100vh - 84px);
  padding: 24px;
  background: linear-gradient(180deg, #f7f9ff 0%, #f3f6fb 100%);
  font-family: "Microsoft YaHei", "PingFang SC", sans-serif;
  color: #2f3545;
}

.dashboard-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;

  h2 {
    margin: 0;
    font-size: 36px;
    font-weight: 700;
    color: #1f2738;
  }
}

.brand-badge {
  display: inline-flex;
  align-items: center;
  padding: 10px 16px;
  border: 1px solid rgba(95, 132, 255, 0.12);
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.9);
  box-shadow: 0 8px 24px rgba(110, 125, 163, 0.12);
  font-size: 14px;
  color: #7a8499;
}

.brand-dot {
  width: 10px;
  height: 10px;
  margin-right: 10px;
  border-radius: 50%;
  background: linear-gradient(135deg, #4f7cff, #8ec5ff);
  box-shadow: 0 0 0 6px rgba(79, 124, 255, 0.12);
}

.stats-row,
.chart-row,
.list-row {
  margin-bottom: 20px;
}

.stat-card,
.panel {
  position: relative;
  overflow: hidden;
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.92);
  box-shadow: 0 14px 38px rgba(133, 146, 181, 0.14);
}

.stat-card {
  display: flex;
  align-items: stretch;
  justify-content: space-between;
  min-height: 126px;
  padding: 22px;
  margin-bottom: 20px;

  &::before {
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    content: "";
  }
}

.stat-card__content {
  position: relative;
  z-index: 1;
}

.stat-card__title {
  margin: 0 0 14px;
  font-size: 15px;
  font-weight: 600;
}

.stat-card__value {
  margin: 0 0 12px;
  font-size: 24px;
  font-weight: 700;
  color: #454d60;
}

.stat-card__extra {
  margin: 0;
  font-size: 14px;
  color: #7f8799;
}

.stat-card__decor {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 84px;
  font-size: 42px;
  opacity: 0.14;

  &::before,
  &::after {
    position: absolute;
    border-radius: 50%;
    content: "";
  }

  &::before {
    width: 56px;
    height: 56px;
    top: 0;
    right: 0;
    background: currentColor;
    opacity: 0.18;
  }

  &::after {
    width: 34px;
    height: 34px;
    right: 40px;
    bottom: 0;
    background: currentColor;
    opacity: 0.1;
  }
}

.theme-blue {
  &::before {
    background: #4f7cff;
  }

  .stat-card__title,
  .stat-card__decor {
    color: #4f7cff;
  }
}

.theme-green {
  &::before {
    background: #31d0a0;
  }

  .stat-card__title,
  .stat-card__decor {
    color: #31d0a0;
  }
}

.theme-cyan {
  &::before {
    background: #35c8f1;
  }

  .stat-card__title,
  .stat-card__decor {
    color: #35c8f1;
  }
}

.theme-gold {
  &::before {
    background: #ffc63b;
  }

  .stat-card__title,
  .stat-card__decor {
    color: #ffc63b;
  }
}

.panel {
  min-height: 100%;
}

.panel__title {
  display: flex;
  align-items: center;
  padding: 18px 22px;
  border-bottom: 1px solid #eef2fb;
  font-size: 24px;
  font-weight: 700;

  i {
    margin-right: 8px;
    font-size: 18px;
  }

  span {
    font-size: 18px;
  }
}

.panel__title--primary {
  color: #5a76ff;
}

.panel__title--danger {
  color: #ff5f6d;
}

.panel__title--info {
  color: #3b82f6;
}

.chart {
  width: 100%;
}

.chart--line,
.chart--pie {
  height: 320px;
  padding: 8px 8px 0;
}

.product-list,
.user-list {
  padding: 8px 0;
}

.product-item,
.user-item {
  display: flex;
  align-items: center;
  padding: 18px 22px;
}

.product-item + .product-item,
.user-item + .user-item {
  border-top: 1px solid #f2f5fb;
}

.product-item__rank {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  margin-right: 14px;
  border-radius: 10px;
  background: #f5f7ff;
  font-size: 13px;
  font-weight: 700;
  color: #4c5670;
}

.product-item__info,
.user-item__info {
  flex: 1;
}

.product-item__name,
.user-item__name {
  margin-bottom: 6px;
  font-size: 18px;
  font-weight: 600;
  color: #30384a;
}

.product-item__meta,
.user-item__meta,
.user-item__time {
  font-size: 14px;
  color: #818aa0;
}

.product-item__amount {
  font-size: 26px;
  font-weight: 700;
  color: #14a36f;
}

.user-item__avatar {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  margin-right: 14px;
  border-radius: 50%;
  background: #f5f8ff;
  color: #5f7eff;
  font-size: 16px;
}

@media screen and (max-width: 992px) {
  .home {
    padding: 16px;
  }

  .dashboard-header {
    align-items: flex-start;
    flex-direction: column;

    h2 {
      margin-bottom: 12px;
      font-size: 30px;
    }
  }

  .chart--line,
  .chart--pie {
    height: 280px;
  }
}

@media screen and (max-width: 768px) {
  .stat-card {
    min-height: 112px;
    padding: 18px;
  }

  .panel__title span {
    font-size: 16px;
  }

  .product-item,
  .user-item {
    padding: 16px;
  }

  .product-item__name,
  .user-item__name {
    font-size: 16px;
  }

  .product-item__amount {
    font-size: 20px;
  }
}
</style>
