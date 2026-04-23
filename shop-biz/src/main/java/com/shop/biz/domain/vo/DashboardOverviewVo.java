package com.shop.biz.domain.vo;

import java.math.BigDecimal;
import java.util.ArrayList;
import java.util.List;

public class DashboardOverviewVo {

    private BigDecimal todayIncome;
    private BigDecimal yesterdayIncome;
    private Long todayOrders;
    private BigDecimal conversionRate;
    private Long totalUsers;
    private Long todayNewUsers;
    private Long pendingOrders;
    private Long paidOrders;
    private Long unpaidOrders;
    private List<String> revenueDates = new ArrayList<>();
    private List<BigDecimal> revenueValues = new ArrayList<>();
    private List<DashboardProductVo> topProducts = new ArrayList<>();
    private List<DashboardUserVo> latestUsers = new ArrayList<>();

    public BigDecimal getTodayIncome() {
        return todayIncome;
    }

    public void setTodayIncome(BigDecimal todayIncome) {
        this.todayIncome = todayIncome;
    }

    public BigDecimal getYesterdayIncome() {
        return yesterdayIncome;
    }

    public void setYesterdayIncome(BigDecimal yesterdayIncome) {
        this.yesterdayIncome = yesterdayIncome;
    }

    public Long getTodayOrders() {
        return todayOrders;
    }

    public void setTodayOrders(Long todayOrders) {
        this.todayOrders = todayOrders;
    }

    public BigDecimal getConversionRate() {
        return conversionRate;
    }

    public void setConversionRate(BigDecimal conversionRate) {
        this.conversionRate = conversionRate;
    }

    public Long getTotalUsers() {
        return totalUsers;
    }

    public void setTotalUsers(Long totalUsers) {
        this.totalUsers = totalUsers;
    }

    public Long getTodayNewUsers() {
        return todayNewUsers;
    }

    public void setTodayNewUsers(Long todayNewUsers) {
        this.todayNewUsers = todayNewUsers;
    }

    public Long getPendingOrders() {
        return pendingOrders;
    }

    public void setPendingOrders(Long pendingOrders) {
        this.pendingOrders = pendingOrders;
    }

    public Long getPaidOrders() {
        return paidOrders;
    }

    public void setPaidOrders(Long paidOrders) {
        this.paidOrders = paidOrders;
    }

    public Long getUnpaidOrders() {
        return unpaidOrders;
    }

    public void setUnpaidOrders(Long unpaidOrders) {
        this.unpaidOrders = unpaidOrders;
    }

    public List<String> getRevenueDates() {
        return revenueDates;
    }

    public void setRevenueDates(List<String> revenueDates) {
        this.revenueDates = revenueDates;
    }

    public List<BigDecimal> getRevenueValues() {
        return revenueValues;
    }

    public void setRevenueValues(List<BigDecimal> revenueValues) {
        this.revenueValues = revenueValues;
    }

    public List<DashboardProductVo> getTopProducts() {
        return topProducts;
    }

    public void setTopProducts(List<DashboardProductVo> topProducts) {
        this.topProducts = topProducts;
    }

    public List<DashboardUserVo> getLatestUsers() {
        return latestUsers;
    }

    public void setLatestUsers(List<DashboardUserVo> latestUsers) {
        this.latestUsers = latestUsers;
    }
}
