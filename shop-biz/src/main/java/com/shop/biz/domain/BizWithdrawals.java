package com.shop.biz.domain;

import java.math.BigDecimal;
import java.util.Date;
import com.fasterxml.jackson.annotation.JsonFormat;
import org.apache.commons.lang3.builder.ToStringBuilder;
import org.apache.commons.lang3.builder.ToStringStyle;
import com.shop.common.annotation.Excel;
import com.shop.common.core.domain.BaseEntity;

/**
 * 提现申请对象 biz_withdrawals
 * 
 * @author shop
 * @date 2026-04-17
 */
public class BizWithdrawals extends BaseEntity
{
    private static final long serialVersionUID = 1L;

    /** 提现申请ID */
    private Long id;

    /** 提现单号 */
    @Excel(name = "提现单号")
    private String withdrawNo;

    /** 用户ID */
    @Excel(name = "用户ID")
    private Long userId;

    /** 提现金额 */
    @Excel(name = "提现金额")
    private BigDecimal money;

    /** 收款码图片 */
    @Excel(name = "收款码图片")
    private String qrcode;

    /** 状态：0待审核 1已打款 3已驳回 */
    @Excel(name = "状态：0待审核 1已打款 3已驳回")
    private String status;

    /** 驳回原因 */
    @Excel(name = "驳回原因")
    private String reason;

    /** 审核人 */
    @Excel(name = "审核人")
    private String auditBy;

    /** 审核时间 */
    @JsonFormat(pattern = "yyyy-MM-dd")
    @Excel(name = "审核时间", width = 30, dateFormat = "yyyy-MM-dd")
    private Date auditTime;

    /** 打款人 */
    @Excel(name = "打款人")
    private String payBy;

    /** 打款时间 */
    @JsonFormat(pattern = "yyyy-MM-dd")
    @Excel(name = "打款时间", width = 30, dateFormat = "yyyy-MM-dd")
    private Date payTime;

    /** 删除标志（0存在 1删除） */
    private String delFlag;

    private String nickName;


    public String getNickName() {
        return nickName;
    }

    public void setNickName(String nickName) {
        this.nickName = nickName;
    }

    public void setId(Long id)
    {
        this.id = id;
    }

    public Long getId() 
    {
        return id;
    }

    public void setWithdrawNo(String withdrawNo) 
    {
        this.withdrawNo = withdrawNo;
    }

    public String getWithdrawNo() 
    {
        return withdrawNo;
    }

    public void setUserId(Long userId) 
    {
        this.userId = userId;
    }

    public Long getUserId() 
    {
        return userId;
    }

    public void setMoney(BigDecimal money) 
    {
        this.money = money;
    }

    public BigDecimal getMoney() 
    {
        return money;
    }

    public void setQrcode(String qrcode) 
    {
        this.qrcode = qrcode;
    }

    public String getQrcode() 
    {
        return qrcode;
    }

    public void setStatus(String status) 
    {
        this.status = status;
    }

    public String getStatus() 
    {
        return status;
    }

    public void setReason(String reason) 
    {
        this.reason = reason;
    }

    public String getReason() 
    {
        return reason;
    }

    public void setAuditBy(String auditBy) 
    {
        this.auditBy = auditBy;
    }

    public String getAuditBy() 
    {
        return auditBy;
    }

    public void setAuditTime(Date auditTime) 
    {
        this.auditTime = auditTime;
    }

    public Date getAuditTime() 
    {
        return auditTime;
    }

    public void setPayBy(String payBy) 
    {
        this.payBy = payBy;
    }

    public String getPayBy() 
    {
        return payBy;
    }

    public void setPayTime(Date payTime) 
    {
        this.payTime = payTime;
    }

    public Date getPayTime() 
    {
        return payTime;
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
            .append("withdrawNo", getWithdrawNo())
            .append("userId", getUserId())
            .append("money", getMoney())
            .append("qrcode", getQrcode())
            .append("status", getStatus())
            .append("reason", getReason())
            .append("auditBy", getAuditBy())
            .append("auditTime", getAuditTime())
            .append("payBy", getPayBy())
            .append("payTime", getPayTime())
            .append("createBy", getCreateBy())
            .append("createTime", getCreateTime())
            .append("updateBy", getUpdateBy())
            .append("updateTime", getUpdateTime())
            .append("delFlag", getDelFlag())
            .toString();
    }
}
