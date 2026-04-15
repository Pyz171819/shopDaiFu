package com.shop.biz.service.impl;

import com.shop.biz.domain.bo.WxPayConfigBO;
import com.shop.biz.mapper.BizConfigMapper;
import com.shop.biz.service.IBizWxPayConfigService;
import org.apache.commons.lang3.StringUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;

@Service
public class BizWxPayConfigServiceImpl implements IBizWxPayConfigService {

    @Autowired
    private BizConfigMapper bizConfigMapper;

    @Override
    public WxPayConfigBO loadConfig() {
        WxPayConfigBO bo = new WxPayConfigBO();

        bo.setH5BaseUrl(trimToEmpty(getValue("h5_base_url")));

        bo.setAppid(trimToEmpty(getValue("wxpay_appid")));
        bo.setSecret(trimToEmpty(getValue("wxpay_secret")));

        bo.setMchid(trimToEmpty(getValue("wxpay_mchid")));
        bo.setKeyV2(trimToEmpty(getValue("wxpay_key")));
        bo.setApiV3Key(trimToEmpty(getValue("wxpay_apiv3_key")));
        bo.setCertSerialNo(trimToEmpty(getValue("wxpay_cert_serial_no")));
        bo.setPrivateKeyPath(trimToEmpty(getValue("wxpay_private_key_path")));

        bo.setMockEnable("1".equals(trimToEmpty(getValue("wxpay_mock_enable"))));
        bo.setMockOpenid(trimToEmpty(getValue("mock_openid")));
        bo.setAuthScope(defaultIfBlank(getValue("wx_auth_scope"), "snsapi_base"));

        return bo;
    }

    @Override
    public String buildOrderPageUrl(String outTradeNo) {
        WxPayConfigBO config = loadConfig();
        String baseUrl = removeTrailingSlash(config.getH5BaseUrl());
        return baseUrl + "/h5/order?outTradeNo=" + trimToEmpty(outTradeNo);
    }

    @Override
    public String buildWxAuthCallbackUrl() {
        WxPayConfigBO config = loadConfig();
        String baseUrl = removeTrailingSlash(config.getH5BaseUrl());
        return baseUrl + "/h5/auth/callback";
    }

    @Override
    public String buildWxPayNotifyUrl() {
        WxPayConfigBO config = loadConfig();
        String baseUrl = removeTrailingSlash(config.getH5BaseUrl());
        return baseUrl + "/biz/pay/notify/wxpay";
    }

    private String getValue(String key) {
        return bizConfigMapper.selectValueByKey(key);
    }

    private String trimToEmpty(String str) {
        return str == null ? "" : str.trim();
    }

    private String defaultIfBlank(String value, String defaultValue) {
        return StringUtils.isBlank(value) ? defaultValue : value;
    }

    private String removeTrailingSlash(String url) {
        String value = trimToEmpty(url);
        if (value.endsWith("/")) {
            return value.substring(0, value.length() - 1);
        }
        return value;
    }
}