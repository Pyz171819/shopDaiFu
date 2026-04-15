package com.shop.biz.domain.pay;

import java.util.Map;

public class BizOrderPayResp {

    /**
     * 支付场景：NATIVE / JSAPI / H5
     */
    private String payScene;

    /**
     * 商户订单号
     */
    private String tradeNo;

    /**
     * Native 扫码链接
     */
    private String codeUrl;

    /**
     * H5 跳转链接
     */
    private String h5Url;

    /**
     * JSAPI 调起支付参数
     */
    private Map<String, String> payParams;

    /**
     * 订单金额
     */
    private String amount;

    /**
     * 订单过期时间
     */
    private String expireTime;

    public String getPayScene() {
        return payScene;
    }

    public void setPayScene(String payScene) {
        this.payScene = payScene;
    }

    public String getTradeNo() {
        return tradeNo;
    }

    public void setTradeNo(String tradeNo) {
        this.tradeNo = tradeNo;
    }

    public String getCodeUrl() {
        return codeUrl;
    }

    public void setCodeUrl(String codeUrl) {
        this.codeUrl = codeUrl;
    }

    public String getH5Url() {
        return h5Url;
    }

    public void setH5Url(String h5Url) {
        this.h5Url = h5Url;
    }

    public Map<String, String> getPayParams() {
        return payParams;
    }

    public void setPayParams(Map<String, String> payParams) {
        this.payParams = payParams;
    }

    public String getAmount() {
        return amount;
    }

    public void setAmount(String amount) {
        this.amount = amount;
    }

    public String getExpireTime() {
        return expireTime;
    }

    public void setExpireTime(String expireTime) {
        this.expireTime = expireTime;
    }
}