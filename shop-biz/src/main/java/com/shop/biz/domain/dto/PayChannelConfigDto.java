package com.shop.biz.domain.dto;

import java.io.Serializable;

public class PayChannelConfigDto implements Serializable {

    private static final long serialVersionUID = 1L;

    /**
     * 支付通道：wxpay / epay / manual / ysm
     */
    private String payChannel;

    /**
     * 微信直连配置
     */
    private String wxpayAppid;
    private String wxpaySecret;
    private String wxpayMchid;
    private String wxpayKey;

    /**
     * 易支付配置
     */
    private String epayApi;
    private String epayId;
    private String epayKey;

    /**
     * 个人收款码
     */
    private String manualQrcode;

    /**
     * YSM 配置
     */
    private String ysmApi;
    private String ysmId;
    private String ysmKey;
    private String ysmPayType;

    public String getPayChannel() {
        return payChannel;
    }

    public void setPayChannel(String payChannel) {
        this.payChannel = payChannel;
    }

    public String getWxpayAppid() {
        return wxpayAppid;
    }

    public void setWxpayAppid(String wxpayAppid) {
        this.wxpayAppid = wxpayAppid;
    }

    public String getWxpaySecret() {
        return wxpaySecret;
    }

    public void setWxpaySecret(String wxpaySecret) {
        this.wxpaySecret = wxpaySecret;
    }

    public String getWxpayMchid() {
        return wxpayMchid;
    }

    public void setWxpayMchid(String wxpayMchid) {
        this.wxpayMchid = wxpayMchid;
    }

    public String getWxpayKey() {
        return wxpayKey;
    }

    public void setWxpayKey(String wxpayKey) {
        this.wxpayKey = wxpayKey;
    }

    public String getEpayApi() {
        return epayApi;
    }

    public void setEpayApi(String epayApi) {
        this.epayApi = epayApi;
    }

    public String getEpayId() {
        return epayId;
    }

    public void setEpayId(String epayId) {
        this.epayId = epayId;
    }

    public String getEpayKey() {
        return epayKey;
    }

    public void setEpayKey(String epayKey) {
        this.epayKey = epayKey;
    }

    public String getManualQrcode() {
        return manualQrcode;
    }

    public void setManualQrcode(String manualQrcode) {
        this.manualQrcode = manualQrcode;
    }

    public String getYsmApi() {
        return ysmApi;
    }

    public void setYsmApi(String ysmApi) {
        this.ysmApi = ysmApi;
    }

    public String getYsmId() {
        return ysmId;
    }

    public void setYsmId(String ysmId) {
        this.ysmId = ysmId;
    }

    public String getYsmKey() {
        return ysmKey;
    }

    public void setYsmKey(String ysmKey) {
        this.ysmKey = ysmKey;
    }

    public String getYsmPayType() {
        return ysmPayType;
    }

    public void setYsmPayType(String ysmPayType) {
        this.ysmPayType = ysmPayType;
    }
}