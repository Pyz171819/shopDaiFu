package com.shop.biz.service;

public interface IWechatOauthService {

    /**
     * 构建微信网页授权地址
     */
    String buildAuthorizeUrl(String callbackUrl);

    /**
     * 通过 code 换取 openId
     */
    String getOpenIdByCode(String code);
}