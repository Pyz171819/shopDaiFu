package com.shop.biz.domain.vo;

import java.math.BigDecimal;

public class DashboardRevenuePointVo {

    private String dayLabel;
    private BigDecimal amount;

    public String getDayLabel() {
        return dayLabel;
    }

    public void setDayLabel(String dayLabel) {
        this.dayLabel = dayLabel;
    }

    public BigDecimal getAmount() {
        return amount;
    }

    public void setAmount(BigDecimal amount) {
        this.amount = amount;
    }
}
