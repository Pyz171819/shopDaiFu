package com.shop.biz.domain.pay;

public class BizWxPayV2Config {

    private String appId;
    private String mchId;
    private String key;
    private String secret;
    private String h5BaseUrl;
    private String mockOpenId;
    private String daifuSwitch;
    private String payChannel;

    public String getAppId() {
        return appId;
    }

    public void setAppId(String appId) {
        this.appId = appId;
    }

    public String getMchId() {
        return mchId;
    }

    public void setMchId(String mchId) {
        this.mchId = mchId;
    }

    public String getKey() {
        return key;
    }

    public void setKey(String key) {
        this.key = key;
    }

    public String getSecret() {
        return secret;
    }

    public void setSecret(String secret) {
        this.secret = secret;
    }

    public String getH5BaseUrl() {
        return h5BaseUrl;
    }

    public void setH5BaseUrl(String h5BaseUrl) {
        this.h5BaseUrl = h5BaseUrl;
    }

    public String getMockOpenId() {
        return mockOpenId;
    }

    public void setMockOpenId(String mockOpenId) {
        this.mockOpenId = mockOpenId;
    }

    public String getDaifuSwitch() {
        return daifuSwitch;
    }

    public void setDaifuSwitch(String daifuSwitch) {
        this.daifuSwitch = daifuSwitch;
    }

    public String getPayChannel() {
        return payChannel;
    }

    public void setPayChannel(String payChannel) {
        this.payChannel = payChannel;
    }
}