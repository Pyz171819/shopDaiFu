package com.shop.biz.service.impl;

import com.alibaba.fastjson2.JSON;
import com.alibaba.fastjson2.JSONObject;
import com.shop.biz.domain.vo.WechatJsapiSignatureVo;
import com.shop.biz.service.IBizConfigService;
import com.shop.biz.service.IWechatJsapiService;
import com.shop.common.exception.ServiceException;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.web.client.RestTemplate;

import java.nio.charset.StandardCharsets;
import java.security.MessageDigest;
import java.util.UUID;
import java.util.concurrent.ConcurrentHashMap;

@Service
public class WechatJsapiServiceImpl implements IWechatJsapiService {

    @Autowired
    private IBizConfigService bizConfigService;

    @Autowired
    private RestTemplate restTemplate;

    /**
     * 简单缓存 access_token / jsapi_ticket
     */
    private static final ConcurrentHashMap<String, CacheItem> CACHE = new ConcurrentHashMap<>();

    @Override
    public WechatJsapiSignatureVo getJsapiSignature(String url) {
        if (isBlank(url)) {
            throw new ServiceException("签名URL不能为空");
        }

        String appId = bizConfigService.selectConfigByKey("wxpay_appid");
        String secret = bizConfigService.selectConfigByKey("wxpay_secret");

        if (isBlank(appId)) {
            throw new ServiceException("未配置 wxpay_appid");
        }
        if (isBlank(secret)) {
            throw new ServiceException("未配置 wxpay_secret");
        }

        String accessToken = getAccessToken(appId, secret);
        String jsapiTicket = getJsapiTicket(accessToken);

        long timestamp = System.currentTimeMillis() / 1000;
        String nonceStr = UUID.randomUUID().toString().replace("-", "");
        String signature = buildSignature(jsapiTicket, nonceStr, timestamp, url);

        WechatJsapiSignatureVo vo = new WechatJsapiSignatureVo();
        vo.setAppId(appId);
        vo.setTimestamp(timestamp);
        vo.setNonceStr(nonceStr);
        vo.setSignature(signature);
        return vo;
    }

    private String getAccessToken(String appId, String secret) {
        String cacheKey = "wechat_access_token_" + appId;
        CacheItem cached = CACHE.get(cacheKey);
        if (cached != null && !cached.isExpired()) {
            return cached.getValue();
        }

        String api = "https://api.weixin.qq.com/cgi-bin/token"
                + "?grant_type=client_credential"
                + "&appid=" + appId
                + "&secret=" + secret;

        String body;
        try {
            body = restTemplate.getForObject(api, String.class);
        } catch (Exception e) {
            throw new ServiceException("获取 access_token 失败：" + e.getMessage());
        }

        if (isBlank(body)) {
            throw new ServiceException("获取 access_token 失败：返回为空");
        }

        JSONObject json = JSON.parseObject(body);
        String accessToken = json.getString("access_token");
        Integer expiresIn = json.getInteger("expires_in");

        if (isBlank(accessToken)) {
            throw new ServiceException("获取 access_token 失败：" + body);
        }

        long expireAt = System.currentTimeMillis()
                + (long) (expiresIn == null ? 7000 : expiresIn - 200) * 1000;
        CACHE.put(cacheKey, new CacheItem(accessToken, expireAt));
        return accessToken;
    }

    private String getJsapiTicket(String accessToken) {
        String cacheKey = "wechat_jsapi_ticket_" + accessToken;
        CacheItem cached = CACHE.get(cacheKey);
        if (cached != null && !cached.isExpired()) {
            return cached.getValue();
        }

        String api = "https://api.weixin.qq.com/cgi-bin/ticket/getticket"
                + "?access_token=" + accessToken
                + "&type=jsapi";

        String body;
        try {
            body = restTemplate.getForObject(api, String.class);
        } catch (Exception e) {
            throw new ServiceException("获取 jsapi_ticket 失败：" + e.getMessage());
        }

        if (isBlank(body)) {
            throw new ServiceException("获取 jsapi_ticket 失败：返回为空");
        }

        JSONObject json = JSON.parseObject(body);
        Integer errCode = json.getInteger("errcode");
        String ticket = json.getString("ticket");
        Integer expiresIn = json.getInteger("expires_in");

        if (errCode != null && errCode != 0) {
            throw new ServiceException("获取 jsapi_ticket 失败：" + body);
        }
        if (isBlank(ticket)) {
            throw new ServiceException("获取 jsapi_ticket 失败：" + body);
        }

        long expireAt = System.currentTimeMillis()
                + (long) (expiresIn == null ? 7000 : expiresIn - 200) * 1000;
        CACHE.put(cacheKey, new CacheItem(ticket, expireAt));
        return ticket;
    }

    private String buildSignature(String jsapiTicket, String nonceStr, long timestamp, String url) {
        String raw = "jsapi_ticket=" + jsapiTicket
                + "&noncestr=" + nonceStr
                + "&timestamp=" + timestamp
                + "&url=" + url;
        return sha1(raw);
    }

    private String sha1(String text) {
        try {
            MessageDigest md = MessageDigest.getInstance("SHA-1");
            byte[] digest = md.digest(text.getBytes(StandardCharsets.UTF_8));
            StringBuilder sb = new StringBuilder();
            for (byte b : digest) {
                String hex = Integer.toHexString(b & 0xff);
                if (hex.length() < 2) {
                    sb.append('0');
                }
                sb.append(hex);
            }
            return sb.toString();
        } catch (Exception e) {
            throw new ServiceException("生成微信签名失败：" + e.getMessage());
        }
    }

    private boolean isBlank(String str) {
        return str == null || str.trim().isEmpty();
    }

    private static class CacheItem {
        private final String value;
        private final long expireAt;

        public CacheItem(String value, long expireAt) {
            this.value = value;
            this.expireAt = expireAt;
        }

        public String getValue() {
            return value;
        }

        public boolean isExpired() {
            return System.currentTimeMillis() >= expireAt;
        }
    }
}