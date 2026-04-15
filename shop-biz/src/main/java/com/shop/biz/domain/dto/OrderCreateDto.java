package com.shop.biz.domain.dto;

import java.io.Serializable;
import java.util.List;

public class OrderCreateDto implements Serializable {

    private static final long serialVersionUID = 1L;

    /**
     * 订单项
     */
    private List<OrderItemDto> items;

    /**
     * 页面模板样式
     */
    private String template;

    /**
     * 前台模板标识
     */
    private String tpl;

    public List<OrderItemDto> getItems() {
        return items;
    }

    public void setItems(List<OrderItemDto> items) {
        this.items = items;
    }

    public String getTemplate() {
        return template;
    }

    public void setTemplate(String template) {
        this.template = template;
    }

    public String getTpl() {
        return tpl;
    }

    public void setTpl(String tpl) {
        this.tpl = tpl;
    }
}