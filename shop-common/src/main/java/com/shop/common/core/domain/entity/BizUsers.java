package com.shop.common.core.domain.entity;

import java.math.BigDecimal;
import org.apache.commons.lang3.builder.ToStringBuilder;
import org.apache.commons.lang3.builder.ToStringStyle;
import com.shop.common.annotation.Excel;
import com.shop.common.core.domain.BaseEntity;

/**
 * 前台用户业务扩展对象 biz_users
 * 
 * @author shop
 * @date 2026-04-11
 */
public class BizUsers extends BaseEntity
{
    private static final long serialVersionUID = 1L;

    /** 业务用户ID */
    private Long id;

    /** 关联sys_user.user_id */
    @Excel(name = "关联sys_user.user_id")
    private Long sysUserId;

    /** 头像地址 */
    @Excel(name = "头像地址")
    private String avatar;

    /** 注册IP */
    @Excel(name = "注册IP")
    private String regIp;

    /** 佣金比例% */
    @Excel(name = "佣金比例%")
    private BigDecimal commissionRate;

    /** 可提现余额 */
    @Excel(name = "可提现余额")
    private BigDecimal balance;

    /** 收款码路径 */
    @Excel(name = "收款码路径")
    private String payCode;

    /** 支付权限：0无 1有 */
    @Excel(name = "支付权限：0无 1有")
    private String permPayment;

    /** 用户支付配置信息 */
    @Excel(name = "用户支付配置信息")
    private String payConfig;

    /** 上级用户ID */
    @Excel(name = "上级用户ID")
    private Long pid;

    /** 代理比例% */
    @Excel(name = "代理比例%")
    private BigDecimal agentRate;

    /** 发布商品权限：0无 1有 */
    @Excel(name = "发布商品权限：0无 1有")
    private String permAddProduct;

    /** 删除标志（0存在 2删除） */
    private String delFlag;

    public void setId(Long id) 
    {
        this.id = id;
    }

    public Long getId() 
    {
        return id;
    }

    public void setSysUserId(Long sysUserId) 
    {
        this.sysUserId = sysUserId;
    }

    public Long getSysUserId() 
    {
        return sysUserId;
    }

    public void setAvatar(String avatar) 
    {
        this.avatar = avatar;
    }

    public String getAvatar() 
    {
        return avatar;
    }

    public void setRegIp(String regIp) 
    {
        this.regIp = regIp;
    }

    public String getRegIp() 
    {
        return regIp;
    }

    public void setCommissionRate(BigDecimal commissionRate) 
    {
        this.commissionRate = commissionRate;
    }

    public BigDecimal getCommissionRate() 
    {
        return commissionRate;
    }

    public void setBalance(BigDecimal balance) 
    {
        this.balance = balance;
    }

    public BigDecimal getBalance() 
    {
        return balance;
    }

    public void setPayCode(String payCode) 
    {
        this.payCode = payCode;
    }

    public String getPayCode() 
    {
        return payCode;
    }

    public void setPermPayment(String permPayment) 
    {
        this.permPayment = permPayment;
    }

    public String getPermPayment() 
    {
        return permPayment;
    }

    public void setPayConfig(String payConfig) 
    {
        this.payConfig = payConfig;
    }

    public String getPayConfig() 
    {
        return payConfig;
    }

    public void setPid(Long pid) 
    {
        this.pid = pid;
    }

    public Long getPid() 
    {
        return pid;
    }

    public void setAgentRate(BigDecimal agentRate) 
    {
        this.agentRate = agentRate;
    }

    public BigDecimal getAgentRate() 
    {
        return agentRate;
    }

    public void setPermAddProduct(String permAddProduct) 
    {
        this.permAddProduct = permAddProduct;
    }

    public String getPermAddProduct() 
    {
        return permAddProduct;
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
            .append("sysUserId", getSysUserId())
            .append("avatar", getAvatar())
            .append("regIp", getRegIp())
            .append("commissionRate", getCommissionRate())
            .append("balance", getBalance())
            .append("payCode", getPayCode())
            .append("permPayment", getPermPayment())
            .append("payConfig", getPayConfig())
            .append("pid", getPid())
            .append("agentRate", getAgentRate())
            .append("permAddProduct", getPermAddProduct())
            .append("createBy", getCreateBy())
            .append("createTime", getCreateTime())
            .append("updateBy", getUpdateBy())
            .append("updateTime", getUpdateTime())
            .append("delFlag", getDelFlag())
            .toString();
    }
}
