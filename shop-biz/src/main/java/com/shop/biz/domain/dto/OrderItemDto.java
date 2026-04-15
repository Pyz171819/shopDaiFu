package com.shop.biz.domain.dto;

import java.io.Serializable;

public class OrderItemDto implements Serializable {

    private static final long serialVersionUID = 1L;

    /**
     * 商品ID
     */
    private Integer productId;

    /**
     * 数量
     */
    private Integer quantity;

    public Integer getProductId() {
        return productId;
    }

    public void setProductId(Integer productId) {
        this.productId = productId;
    }

    public Integer getQuantity() {
        return quantity;
    }

    public void setQuantity(Integer quantity) {
        this.quantity = quantity;
    }
}