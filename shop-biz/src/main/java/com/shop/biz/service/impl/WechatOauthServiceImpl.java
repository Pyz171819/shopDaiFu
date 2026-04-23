package com.shop.biz.service.impl;

import com.fasterxml.jackson.databind.ObjectMapper;
import com.shop.biz.mapper.BizConfigMapper;
import com.shop.biz.service.IWechatOauthService;
import com.shop.common.exception.ServiceException;
import org.apache.commons.lang3.StringUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.web.client.RestTemplate;

import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.util.Map;

@Service
public class WechatOauthServiceImpl implements IWechatOauthService {

    private static final String KEY_WX_APP_ID = "wxpay_appid";
    private static final String KEY_WX_SECRET = "wxpay_secret";

    @Autowired
    private BizConfigMapper bizConfigMapper;

    @Autowired
    private RestTemplate restTemplate;

    @Override
    public String buildAuthorizeUrl(String callbackUrl) {
        String appId = getRequiredConfig(KEY_WX_APP_ID);

        return "https://open.weixin.qq.com/connect/oauth2/authorize"
                + "?appid=" + urlEncode(appId)
                + "&redirect_uri=" + urlEncode(callbackUrl)
                + "&response_type=code"
                + "&scope=snsapi_base"
                + "&state=STATE"
                + "#wechat_redirect";
    }

    @Override
    @SuppressWarnings("unchecked")
    public String getOpenIdByCode(String code) {
        if (StringUtils.isBlank(code)) {
            throw new ServiceException("微信授权失败，code不能为空");
        }

        String appId = getRequiredConfig(KEY_WX_APP_ID);
        String secret = getRequiredConfig(KEY_WX_SECRET);

        String url = "https://api.weixin.qq.com/sns/oauth2/access_token"
                + "?appid=" + urlEncode(appId)
                + "&secret=" + urlEncode(secret)
                + "&code=" + urlEncode(code)
                + "&grant_type=authorization_code";

        String body;
        try {
            body = restTemplate.getForObject(url, String.class);
        } catch (Exception e) {
            throw new ServiceException("请求微信获取openId接口失败：" + e.getMessage());
        }

        System.out.println("wechat oauth response body=" + body);

        if (StringUtils.isBlank(body)) {
            throw new ServiceException("微信获取openId失败，返回为空");
        }

        Map<String, Object> resp;
        try {
            ObjectMapper objectMapper = new ObjectMapper();
            resp = objectMapper.readValue(body, Map.class);
        } catch (Exception e) {
            throw new ServiceException("解析微信openId返回失败：" + e.getMessage());
        }

        Object errCode = resp.get("errcode");
        if (errCode != null) {
            Object errMsg = resp.get("errmsg");
            throw new ServiceException("微信获取openId失败：" + errMsg);
        }

        Object openId = resp.get("openid");
        if (openId == null || StringUtils.isBlank(String.valueOf(openId))) {
            throw new ServiceException("微信获取openId失败，返回openid为空");
        }

        return String.valueOf(openId);
    }

    private String getRequiredConfig(String key) {
        String value = bizConfigMapper.selectValueByKey(key);
        if (StringUtils.isBlank(value)) {
            throw new ServiceException("系统未配置参数：" + key);
        }
        return value.trim();
    }

    private String urlEncode(String value) {
        return URLEncoder.encode(value, StandardCharsets.UTF_8);
    }
}