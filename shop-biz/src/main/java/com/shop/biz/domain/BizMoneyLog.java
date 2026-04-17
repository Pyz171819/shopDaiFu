package com.shop.biz.domain;

import java.math.BigDecimal;
import org.apache.commons.lang3.builder.ToStringBuilder;
import org.apache.commons.lang3.builder.ToStringStyle;
import com.shop.common.annotation.Excel;
import com.shop.common.core.domain.BaseEntity;

/**
 * 资金变动日志对象 biz_money_log
 * 
 * @author shop
 * @date 2026-04-17
 */
public class BizMoneyLog extends BaseEntity
{
    private static final long serialVersionUID = 1L;

    /** 日志ID */
    private Long id;

    /** 用户ID */
    @Excel(name = "用户ID")
    private Long userId;

    /** 变动类型 */
    @Excel(name = "变动类型")
    private String type;

    /** 变动金额 */
    @Excel(name = "变动金额")
    private BigDecimal money;

    /** 变动前余额 */
    @Excel(name = "变动前余额")
    private BigDecimal beforeMoney;

    /** 变动后余额 */
    @Excel(name = "变动后余额")
    private BigDecimal afterMoney;

    /** 删除标志（0存在 1删除） */
    private String delFlag;

    public void setId(Long id) 
    {
        this.id = id;
    }

    public Long getId() 
    {
        return id;
    }

    public void setUserId(Long userId) 
    {
        this.userId = userId;
    }

    public Long getUserId() 
    {
        return userId;
    }

    public void setType(String type) 
    {
        this.type = type;
    }

    public String getType() 
    {
        return type;
    }

    public void setMoney(BigDecimal money) 
    {
        this.money = money;
    }

    public BigDecimal getMoney() 
    {
        return money;
    }

    public void setBeforeMoney(BigDecimal beforeMoney) 
    {
        this.beforeMoney = beforeMoney;
    }

    public BigDecimal getBeforeMoney() 
    {
        return beforeMoney;
    }

    public void setAfterMoney(BigDecimal afterMoney) 
    {
        this.afterMoney = afterMoney;
    }

    public BigDecimal getAfterMoney() 
    {
        return afterMoney;
    }

    public void setDelFlag(String delFlag) 
    {
        this.delFlag = delFlag;
    }

    public String getDelFlag() 
    {
        return delFlag;
    }

    @Override
    public String toString() {
        return new ToStringBuilder(this,ToStringStyle.MULTI_LINE_STYLE)
            .append("id", getId())
            .append("userId", getUserId())
            .append("type", getType())
            .append("money", getMoney())
            .append("beforeMoney", getBeforeMoney())
            .append("afterMoney", getAfterMoney())
            .append("remark", getRemark())
            .append("createBy", getCreateBy())
            .append("createTime", getCreateTime())
            .append("updateBy", getUpdateBy())
            .append("updateTime", getUpdateTime())
            .append("delFlag", getDelFlag())
            .toString();
    }
}
