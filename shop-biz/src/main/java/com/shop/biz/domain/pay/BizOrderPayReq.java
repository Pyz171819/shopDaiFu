package com.shop.biz.domain.pay;

public class BizOrderPayReq {
    /**
     * 商户订单号
     */
    private String tradeNo;

    /**
     * 客户端类型：
     * pc / wechat_h5 / mobile_h5
     */
    private String clientType;

    /**
     * 微信内支付时可能需要
     */
    private String openId;

    public String getTradeNo() {
        return tradeNo;
    }

    public void setTradeNo(String tradeNo) {
        this.tradeNo = tradeNo;
    }

    public String getClientType() {
        return clientType;
    }

    public void setClientType(String clientType) {
        this.clientType = clientType;
    }

    public String getOpenId() {
        return openId;
    }

    public void setOpenId(String openId) {
        this.openId = openId;
    }
}