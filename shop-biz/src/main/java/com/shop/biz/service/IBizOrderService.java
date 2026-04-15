package com.shop.biz.service;

import com.shop.biz.domain.BizOrder;
import com.shop.biz.domain.dto.OrderCreateDto;
import com.shop.biz.domain.pay.BizOrderPayReq;
import com.shop.biz.domain.pay.BizOrderPayResp;
import jakarta.servlet.http.HttpServletRequest;

public interface IBizOrderService {

    /**
     * 创建订单
     */
    BizOrder createOrder(OrderCreateDto dto);

    /**
     * 根据ID查询订单
     */
    BizOrder getOrderById(Long id);

    /**
     * 根据商户订单号查询订单
     */
    BizOrder getOrderByOutTradeNo(String outTradeNo);

    BizOrderPayResp pay(BizOrderPayReq req, HttpServletRequest request);
}