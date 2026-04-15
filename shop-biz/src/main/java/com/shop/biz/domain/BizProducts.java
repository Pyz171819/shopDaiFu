package com.shop.biz.domain;

import java.math.BigDecimal;
import org.apache.commons.lang3.builder.ToStringBuilder;
import org.apache.commons.lang3.builder.ToStringStyle;
import com.shop.common.annotation.Excel;
import com.shop.common.core.domain.BaseEntity;

/**
 * 商品对象 biz_products
 * 
 * @author shop
 * @date 2026-04-11
 */
public class BizProducts extends BaseEntity
{
    private static final long serialVersionUID = 1L;

    /** 商品ID */
    private Long id;

    /** 分类ID（关联biz_categories表） */
    @Excel(name = "分类ID", readConverterExp = "关=联biz_categories表")
    private Long categoryId;

    /** 分类名称 */
    private String categoryName;

    /** 所属用户ID，0为管理员 */
    @Excel(name = "所属用户ID，0为管理员")
    private Long userId;
    private String userName;

    /** 商品名称 */
    @Excel(name = "商品名称")
    private String name;

    /** 商品价格 */
    @Excel(name = "商品价格")
    private BigDecimal price;

    /** 商品图片地址 */
    @Excel(name = "商品图片地址")
    private String image;

    /** 店铺/标签名称 */
    @Excel(name = "店铺/标签名称")
    private String shopName;

    /** 审核状态：0-待审核 1-审核通过 2-审核不通过 */
    @Excel(name = "审核状态：0-待审核 1-审核通过 2-审核不通过")
    private String auditStatus;

    /** 排序值，越大越靠前 */
    private Long sort;

    /** 商品状态：1-正常 0-下架 */
    @Excel(name = "商品状态：1-正常 0-下架")
    private String status;

    @Excel(name = "删除状态：1-正常 0-删除")
    private String delFlag;


    public String getDelFlag() {
        return delFlag;
    }

    public void setDelFlag(String delFlag) {
        this.delFlag = delFlag;
    }

    public String getCategoryName() {
        return categoryName;
    }

    public void setCategoryName(String categoryName) {
        this.categoryName = categoryName;
    }

    public String getUserName() {
        return userName;
    }

    public void setUserName(String userName) {
        this.userName = userName;
    }

    public void setId(Long id)
    {
        this.id = id;
    }

    public Long getId() 
    {
        return id;
    }

    public void setCategoryId(Long categoryId) 
    {
        this.categoryId = categoryId;
    }

    public Long getCategoryId() 
    {
        return categoryId;
    }

    public void setUserId(Long userId) 
    {
        this.userId = userId;
    }

    public Long getUserId() 
    {
        return userId;
    }

    public void setName(String name) 
    {
        this.name = name;
    }

    public String getName() 
    {
        return name;
    }

    public void setPrice(BigDecimal price) 
    {
        this.price = price;
    }

    public BigDecimal getPrice() 
    {
        return price;
    }

    public void setImage(String image) 
    {
        this.image = image;
    }

    public String getImage() 
    {
        return image;
    }

    public void setShopName(String shopName) 
    {
        this.shopName = shopName;
    }

    public String getShopName() 
    {
        return shopName;
    }

    public void setAuditStatus(String auditStatus) 
    {
        this.auditStatus = auditStatus;
    }

    public String getAuditStatus() 
    {
        return auditStatus;
    }

    public void setSort(Long sort) 
    {
        this.sort = sort;
    }

    public Long getSort() 
    {
        return sort;
    }

    public void setStatus(String status) 
    {
        this.status = status;
    }

    public String getStatus() 
    {
        return status;
    }

    @Override
    public String toString() {
        return new ToStringBuilder(this,ToStringStyle.MULTI_LINE_STYLE)
            .append("id", getId())
            .append("categoryId", getCategoryId())
            .append("userId", getUserId())
            .append("name", getName())
            .append("price", getPrice())
            .append("image", getImage())
            .append("shopName", getShopName())
            .append("auditStatus", getAuditStatus())
            .append("sort", getSort())
            .append("status", getStatus())
            .append("createBy", getCreateBy())
            .append("updateBy", getUpdateBy())
            .append("createTime", getCreateTime())
            .append("updateTime", getUpdateTime())
            .toString();
    }
}
