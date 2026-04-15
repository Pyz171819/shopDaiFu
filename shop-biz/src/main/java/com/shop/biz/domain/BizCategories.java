package com.shop.biz.domain;

import org.apache.commons.lang3.builder.ToStringBuilder;
import org.apache.commons.lang3.builder.ToStringStyle;
import com.shop.common.annotation.Excel;
import com.shop.common.core.domain.BaseEntity;

/**
 * 商品分类对象 biz_categories
 * 
 * @author shop
 * @date 2026-04-10
 */
public class BizCategories extends BaseEntity
{
    private static final long serialVersionUID = 1L;

    /** 分类ID */
    private Long id;

    /** 分类名称 */
    @Excel(name = "分类名称")
    private String name;

    /** 排序值（数值越大越靠前） */
    @Excel(name = "排序值", readConverterExp = "数=值越大越靠前")
    private Long sort;

    /** 状态：1-正常 0-禁用 */
    private String status;

    /** 状态：1-正常 0-删除 */
    private String delFalg;







    public void setId(Long id) 
    {
        this.id = id;
    }

    public Long getId() 
    {
        return id;
    }

    public void setName(String name) 
    {
        this.name = name;
    }

    public String getName() 
    {
        return name;
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

    public void setDelFalg(String delFalg) 
    {
        this.delFalg = delFalg;
    }

    public String getDelFalg() 
    {
        return delFalg;
    }

    @Override
    public String toString() {
        return new ToStringBuilder(this,ToStringStyle.MULTI_LINE_STYLE)
            .append("id", getId())
            .append("name", getName())
            .append("sort", getSort())
            .append("status", getStatus())
            .append("createBy", getCreateBy())
            .append("updateBy", getUpdateBy())
            .append("createTime", getCreateTime())
            .append("updateTime", getUpdateTime())
            .append("delFalg", getDelFalg())
            .toString();
    }
}
