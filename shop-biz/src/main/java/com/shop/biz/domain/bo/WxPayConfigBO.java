package com.shop.biz.domain.bo;

import java.io.Serializable;

public class WxPayConfigBO implements Serializable {

    private static final long serialVersionUID = 1L;

    /**
     * 基础地址
     */
    private String h5BaseUrl;

    /**
     * 微信公众号/应用配置
     */
    private String appid;
    private String secret;

    /**
     * 微信支付商户配置
     */
    private String mchid;
    private String keyV2;
    private String apiV3Key;
    private String certSerialNo;
    private String privateKeyPath;

    /**
     * 调试配置
     */
    private boolean mockEnable;
    private String mockOpenid;
    private String authScope;

    public String getH5BaseUrl() {
        return h5BaseUrl;
    }

    public void setH5BaseUrl(String h5BaseUrl) {
        this.h5BaseUrl = h5BaseUrl;
    }

    public String getAppid() {
        return appid;
    }

    public void setAppid(String appid) {
        this.appid = appid;
    }

    public String getSecret() {
        return secret;
    }

    public void setSecret(String secret) {
        this.secret = secret;
    }

    public String getMchid() {
        return mchid;
    }

    public void setMchid(String mchid) {
        this.mchid = mchid;
    }

    public String getKeyV2() {
        return keyV2;
    }

    public void setKeyV2(String keyV2) {
        this.keyV2 = keyV2;
    }

    public String getApiV3Key() {
        return apiV3Key;
    }

    public void setApiV3Key(String apiV3Key) {
        this.apiV3Key = apiV3Key;
    }

    public String getCertSerialNo() {
        return certSerialNo;
    }

    public void setCertSerialNo(String certSerialNo) {
        this.certSerialNo = certSerialNo;
    }

    public String getPrivateKeyPath() {
        return privateKeyPath;
    }

    public void setPrivateKeyPath(String privateKeyPath) {
        this.privateKeyPath = privateKeyPath;
    }

    public boolean isMockEnable() {
        return mockEnable;
    }

    public void setMockEnable(boolean mockEnable) {
        this.mockEnable = mockEnable;
    }

    public String getMockOpenid() {
        return mockOpenid;
    }

    public void setMockOpenid(String mockOpenid) {
        this.mockOpenid = mockOpenid;
    }

    public String getAuthScope() {
        return authScope;
    }

    public void setAuthScope(String authScope) {
        this.authScope = authScope;
    }
}