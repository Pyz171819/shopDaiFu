package com.shop.biz.utils;

import com.shop.biz.domain.BizOrder;

import java.util.Map;

public interface WechatPayGateway {

    /**
     * PC -> Native
     */
    String nativePay(BizOrder order);

    /**
     * 微信内 -> JSAPI
     */
    Map<String, String> jsapiPay(BizOrder order, String openId);

    /**
     * 手机外浏览器 -> H5
     */
    String h5Pay(BizOrder order, String clientIp);
}