package com.shop.biz.service;

import com.shop.biz.domain.bo.WxPayConfigBO;

public interface IBizWxPayConfigService {

    /**
     * 加载微信支付配置
     */
    WxPayConfigBO loadConfig();

    /**
     * 构建订单页地址
     */
    String buildOrderPageUrl(String outTradeNo);

    /**
     * 构建微信授权回调地址
     */
    String buildWxAuthCallbackUrl();

    /**
     * 构建微信支付回调地址
     */
    String buildWxPayNotifyUrl();
}