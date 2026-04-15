package com.shop.biz.domain;

import org.apache.commons.lang3.builder.ToStringBuilder;
import org.apache.commons.lang3.builder.ToStringStyle;
import com.shop.common.annotation.Excel;
import com.shop.common.core.domain.BaseEntity;

/**
 * 分享卡片配置对象 biz_share_card_config
 * 
 * @author shop
 * @date 2026-04-12
 */
public class BizShareCardConfig extends BaseEntity
{
    private static final long serialVersionUID = 1L;

    /** 配置ID */
    private String id;

    /** 模板标识，如 cashier/cashier1/cashier12 */
    @Excel(name = "模板标识，如 cashier/cashier1/cashier12")
    private String tpl;

    /** 卡片名称（后台展示用） */
    @Excel(name = "卡片名称", readConverterExp = "后=台展示用")
    private String cardName;

    /** 副标题/小字 */
    @Excel(name = "副标题/小字")
    private String shareSubtitle;

    /** 微信分享标题 */
    @Excel(name = "微信分享标题")
    private String shareTitle;

    /** 微信分享描述 */
    @Excel(name = "微信分享描述")
    private String shareDesc;

    /** 固定分享图路径 */
    @Excel(name = "固定分享图路径")
    private String shareImage;

    /** 是否使用商品图：0否 1是 */
    @Excel(name = "是否使用商品图：0否 1是")
    private String useProductImage;

    /** 排序值，越大越靠前 */
    @Excel(name = "排序值，越大越靠前")
    private Long sort;

    /** 状态：0停用 1启用 */
    @Excel(name = "状态：0停用 1启用")
    private String status;

    /** 删除标志（0存在 1删除） */
    private String delFlag;

    public void setId(String id) 
    {
        this.id = id;
    }

    public String getId() 
    {
        return id;
    }

    public void setTpl(String tpl) 
    {
        this.tpl = tpl;
    }

    public String getTpl() 
    {
        return tpl;
    }

    public void setCardName(String cardName) 
    {
        this.cardName = cardName;
    }

    public String getCardName() 
    {
        return cardName;
    }

    public void setShareSubtitle(String shareSubtitle) 
    {
        this.shareSubtitle = shareSubtitle;
    }

    public String getShareSubtitle() 
    {
        return shareSubtitle;
    }

    public void setShareTitle(String shareTitle) 
    {
        this.shareTitle = shareTitle;
    }

    public String getShareTitle() 
    {
        return shareTitle;
    }

    public void setShareDesc(String shareDesc) 
    {
        this.shareDesc = shareDesc;
    }

    public String getShareDesc() 
    {
        return shareDesc;
    }

    public void setShareImage(String shareImage) 
    {
        this.shareImage = shareImage;
    }

    public String getShareImage() 
    {
        return shareImage;
    }

    public void setUseProductImage(String useProductImage) 
    {
        this.useProductImage = useProductImage;
    }

    public String getUseProductImage() 
    {
        return useProductImage;
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
            .append("tpl", getTpl())
            .append("cardName", getCardName())
            .append("shareSubtitle", getShareSubtitle())
            .append("shareTitle", getShareTitle())
            .append("shareDesc", getShareDesc())
            .append("shareImage", getShareImage())
            .append("useProductImage", getUseProductImage())
            .append("sort", getSort())
            .append("status", getStatus())
            .append("createBy", getCreateBy())
            .append("createTime", getCreateTime())
            .append("updateBy", getUpdateBy())
            .append("updateTime", getUpdateTime())
            .append("delFlag", getDelFlag())
            .toString();
    }
}
