package com.shop.biz.utils.impl;

import com.shop.biz.domain.BizOrder;
import com.shop.biz.domain.pay.BizWxPayV2Config;
import com.shop.biz.service.IBizConfigService;
import com.shop.biz.utils.WechatPayGateway;
import com.shop.common.exception.ServiceException;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.*;
import org.springframework.stereotype.Component;

import javax.crypto.Mac;
import javax.crypto.spec.SecretKeySpec;
import javax.xml.XMLConstants;
import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import java.io.ByteArrayInputStream;
import java.math.BigDecimal;
import java.nio.charset.StandardCharsets;
import java.security.MessageDigest;
import java.time.ZoneId;
import java.time.format.DateTimeFormatter;
import java.util.*;

import org.springframework.web.client.RestTemplate;
import org.w3c.dom.Document;
import org.w3c.dom.NodeList;

@Component
public class WechatPayGatewayImpl implements WechatPayGateway {

    private static final String UNIFIED_ORDER_URL = "https://api.mch.weixin.qq.com/pay/unifiedorder";
    private static final String SIGN_TYPE = "MD5";
    private static final String FEE_TYPE = "CNY";
    private static final String TRADE_TYPE_NATIVE = "NATIVE";
    private static final String TRADE_TYPE_JSAPI = "JSAPI";
    private static final String TRADE_TYPE_MWEB = "MWEB";

    @Autowired
    private IBizConfigService bizConfigService;

    @Autowired
    private RestTemplate restTemplate;

    @Override
    public String nativePay(BizOrder order) {
        BizWxPayV2Config cfg = loadAndValidateConfig();

        SortedMap<String, String> params = buildBaseParams(cfg, order);
        params.put("trade_type", TRADE_TYPE_NATIVE);
        params.put("product_id", buildProductId(order));
        params.put("sign_type", SIGN_TYPE);
        params.put("sign", createSign(params, cfg.getKey(), SIGN_TYPE));

        Map<String, String> resp = unifiedOrder(params);

        String codeUrl = resp.get("code_url");
        if (isBlank(codeUrl)) {
            throw new ServiceException("微信Native下单失败：未返回 code_url");
        }
        return codeUrl;
    }

    @Override
    public Map<String, String> jsapiPay(BizOrder order, String openId) {
        BizWxPayV2Config cfg = loadAndValidateConfig();

        String finalOpenId = firstNonBlank(openId, order.getOpenid(), cfg.getMockOpenId());
        if (isBlank(finalOpenId)) {
            throw new ServiceException("微信内支付缺少 openid");
        }

        SortedMap<String, String> params = buildBaseParams(cfg, order);
        params.put("trade_type", TRADE_TYPE_JSAPI);
        params.put("openid", finalOpenId);
        params.put("sign_type", SIGN_TYPE);
        params.put("sign", createSign(params, cfg.getKey(), SIGN_TYPE));

        Map<String, String> resp = unifiedOrder(params);
        String prepayId = resp.get("prepay_id");
        if (isBlank(prepayId)) {
            throw new ServiceException("微信JSAPI下单失败：未返回 prepay_id");
        }

        return buildJsapiPayParams(cfg, prepayId);
    }

    @Override
    public String h5Pay(BizOrder order, String clientIp) {
        BizWxPayV2Config cfg = loadAndValidateConfig();

        if (isBlank(clientIp)) {
            throw new ServiceException("H5支付缺少客户端IP");
        }

        SortedMap<String, String> params = buildBaseParams(cfg, order);
        params.put("trade_type", TRADE_TYPE_MWEB);
        params.put("spbill_create_ip", clientIp);

        // 官方 MWEB 需要 scene_info
        Map<String, Object> h5Info = new LinkedHashMap<>();
        h5Info.put("type", "Wap");
        h5Info.put("wap_url", cfg.getH5BaseUrl());
        h5Info.put("wap_name", "代付订单支付");

        Map<String, Object> sceneInfo = new LinkedHashMap<>();
        sceneInfo.put("h5_info", h5Info);

        params.put("scene_info", toJson(sceneInfo));
        params.put("sign_type", SIGN_TYPE);
        params.put("sign", createSign(params, cfg.getKey(), SIGN_TYPE));

        Map<String, String> resp = unifiedOrder(params);
        String mwebUrl = resp.get("mweb_url");
        if (isBlank(mwebUrl)) {
            throw new ServiceException("微信H5下单失败：未返回 mweb_url");
        }
        return mwebUrl;
    }

    /**
     * 调统一下单接口
     */
    private Map<String, String> unifiedOrder(SortedMap<String, String> params) {
        String requestXml = mapToXml(params);

        HttpHeaders headers = new HttpHeaders();
        headers.setContentType(MediaType.APPLICATION_XML);
        HttpEntity<String> entity = new HttpEntity<>(requestXml, headers);

        ResponseEntity<String> response;
        try {
            response = restTemplate.exchange(
                    UNIFIED_ORDER_URL,
                    HttpMethod.POST,
                    entity,
                    String.class
            );
        } catch (Exception e) {
            throw new ServiceException("请求微信统一下单接口失败：" + e.getMessage());
        }

        String body = response.getBody();
        if (isBlank(body)) {
            throw new ServiceException("微信统一下单返回为空");
        }

        Map<String, String> respMap = xmlToMap(body);

        String returnCode = respMap.get("return_code");
        if (!"SUCCESS".equals(returnCode)) {
            throw new ServiceException("微信通信失败：" + defaultString(respMap.get("return_msg"), "未知错误"));
        }

        String resultCode = respMap.get("result_code");
        if (!"SUCCESS".equals(resultCode)) {
            String errCode = respMap.get("err_code");
            String errCodeDes = respMap.get("err_code_des");
            throw new ServiceException("微信下单失败：" + defaultString(errCodeDes, errCode));
        }

        return respMap;
    }

    /**
     * 组装统一下单公共参数
     */
    private SortedMap<String, String> buildBaseParams(BizWxPayV2Config cfg, BizOrder order) {
        SortedMap<String, String> params = new TreeMap<>();
        params.put("appid", cfg.getAppId());
        params.put("mch_id", cfg.getMchId());
        params.put("nonce_str", randomNonceStr());
        params.put("body", buildBody(order));
        params.put("out_trade_no", order.getOutTradeNo());
        params.put("fee_type", FEE_TYPE);
        params.put("total_fee", String.valueOf(toFen(order.getMoney())));
        params.put("spbill_create_ip", "127.0.0.1");
        params.put("notify_url", buildNotifyUrl(cfg));

        if (order.getExpireTime() != null) {
            params.put("time_expire", formatWxTime(order.getExpireTime()));
        }

        return params;
    }

    /**
     * JSAPI 调起支付参数
     */
    private Map<String, String> buildJsapiPayParams(BizWxPayV2Config cfg, String prepayId) {
        SortedMap<String, String> payParams = new TreeMap<>();
        payParams.put("appId", cfg.getAppId());
        payParams.put("timeStamp", String.valueOf(System.currentTimeMillis() / 1000));
        payParams.put("nonceStr", randomNonceStr());
        payParams.put("package", "prepay_id=" + prepayId);
        payParams.put("signType", SIGN_TYPE);

        String paySign = createSign(payParams, cfg.getKey(), SIGN_TYPE);

        Map<String, String> result = new LinkedHashMap<>();
        result.put("appId", payParams.get("appId"));
        result.put("timeStamp", payParams.get("timeStamp"));
        result.put("nonceStr", payParams.get("nonceStr"));
        result.put("package", payParams.get("package"));
        result.put("signType", payParams.get("signType"));
        result.put("paySign", paySign);
        return result;
    }

    /**
     * 微信 V2 签名
     * 默认用 MD5；如你后面想切 HMAC-SHA256，把 SIGN_TYPE 改掉即可
     */
    private String createSign(SortedMap<String, String> params, String key, String signType) {
        StringBuilder sb = new StringBuilder();
        for (Map.Entry<String, String> entry : params.entrySet()) {
            String k = entry.getKey();
            String v = entry.getValue();
            if (!isBlank(v) && !"sign".equals(k)) {
                sb.append(k).append("=").append(v.trim()).append("&");
            }
        }
        sb.append("key=").append(key);

        if ("HMAC-SHA256".equalsIgnoreCase(signType)) {
            return hmacSha256(sb.toString(), key).toUpperCase();
        }
        return md5(sb.toString()).toUpperCase();
    }

    private String md5(String text) {
        try {
            MessageDigest md = MessageDigest.getInstance("MD5");
            byte[] digest = md.digest(text.getBytes(StandardCharsets.UTF_8));
            return bytesToHex(digest);
        } catch (Exception e) {
            throw new ServiceException("MD5签名失败：" + e.getMessage());
        }
    }

    private String hmacSha256(String text, String key) {
        try {
            Mac mac = Mac.getInstance("HmacSHA256");
            SecretKeySpec secretKey = new SecretKeySpec(key.getBytes(StandardCharsets.UTF_8), "HmacSHA256");
            mac.init(secretKey);
            byte[] digest = mac.doFinal(text.getBytes(StandardCharsets.UTF_8));
            return bytesToHex(digest);
        } catch (Exception e) {
            throw new ServiceException("HMAC-SHA256签名失败：" + e.getMessage());
        }
    }

    private String bytesToHex(byte[] bytes) {
        StringBuilder sb = new StringBuilder();
        for (byte b : bytes) {
            String hex = Integer.toHexString(b & 0xFF);
            if (hex.length() == 1) {
                sb.append('0');
            }
            sb.append(hex);
        }
        return sb.toString();
    }

    private String mapToXml(Map<String, String> map) {
        StringBuilder sb = new StringBuilder();
        sb.append("<xml>");
        for (Map.Entry<String, String> entry : map.entrySet()) {
            sb.append("<").append(entry.getKey()).append("><![CDATA[")
                    .append(entry.getValue() == null ? "" : entry.getValue())
                    .append("]]></").append(entry.getKey()).append(">");
        }
        sb.append("</xml>");
        return sb.toString();
    }

    private Map<String, String> xmlToMap(String xml) {
        try {
            DocumentBuilderFactory factory = DocumentBuilderFactory.newInstance();
            factory.setFeature(XMLConstants.FEATURE_SECURE_PROCESSING, true);
            factory.setFeature("http://apache.org/xml/features/disallow-doctype-decl", true);
            factory.setExpandEntityReferences(false);

            DocumentBuilder builder = factory.newDocumentBuilder();
            Document document = builder.parse(new ByteArrayInputStream(xml.getBytes(StandardCharsets.UTF_8)));

            Map<String, String> map = new LinkedHashMap<>();
            NodeList childNodes = document.getDocumentElement().getChildNodes();
            for (int i = 0; i < childNodes.getLength(); i++) {
                if (childNodes.item(i).getNodeType() == org.w3c.dom.Node.ELEMENT_NODE) {
                    map.put(childNodes.item(i).getNodeName(), childNodes.item(i).getTextContent());
                }
            }
            return map;
        } catch (Exception e) {
            throw new ServiceException("解析微信返回XML失败：" + e.getMessage());
        }
    }

    private BizWxPayV2Config loadAndValidateConfig() {
        BizWxPayV2Config cfg = new BizWxPayV2Config();
        cfg.setAppId(required("wxpay_appid"));
        cfg.setMchId(required("wxpay_mchid"));
        cfg.setKey(required("wxpay_key"));
        cfg.setSecret(optional("wxpay_secret"));
        cfg.setH5BaseUrl(trimRightSlash(required("h5_base_url")));
        cfg.setMockOpenId(optional("mock_openid"));
        cfg.setDaifuSwitch(optional("daifu_switch"));
        cfg.setPayChannel(optional("pay_channel"));

        if (!"1".equals(defaultString(cfg.getDaifuSwitch(), "0"))) {
            throw new ServiceException("当前代付功能未开启");
        }
        if (!"wxpay".equalsIgnoreCase(defaultString(cfg.getPayChannel(), ""))) {
            throw new ServiceException("当前支付通道不是微信支付");
        }
        return cfg;
    }

    private String buildNotifyUrl(BizWxPayV2Config cfg) {
        // 按你的实际网关前缀调整
        return cfg.getH5BaseUrl() + "/prod-api/biz/order/notify/wxpay";
    }

    private String buildBody(BizOrder order) {
        if (!isBlank(order.getOrderName())) {
            return order.getOrderName();
        }
        return "订单支付-" + order.getOutTradeNo();
    }

    private String buildProductId(BizOrder order) {
        if (order.getProductId() == null) {
            return String.valueOf(order.getId());
        }
        return String.valueOf(order.getProductId());
    }

    private int toFen(BigDecimal amount) {
        if (amount == null) {
            throw new ServiceException("订单金额不能为空");
        }
        return amount.multiply(new BigDecimal("100")).intValue();
    }

    private String formatWxTime(Date date) {
        return DateTimeFormatter.ofPattern("yyyyMMddHHmmss")
                .format(date.toInstant().atZone(ZoneId.systemDefault()).toLocalDateTime());
    }

    private String randomNonceStr() {
        return UUID.randomUUID().toString().replace("-", "").substring(0, 32);
    }

    private String toJson(Map<String, Object> map) {
        StringBuilder sb = new StringBuilder();
        sb.append("{");
        Iterator<Map.Entry<String, Object>> it = map.entrySet().iterator();
        while (it.hasNext()) {
            Map.Entry<String, Object> entry = it.next();
            sb.append("\"").append(entry.getKey()).append("\":");
            if (entry.getValue() instanceof Map) {
                sb.append(toJson((Map<String, Object>) entry.getValue()));
            } else {
                sb.append("\"").append(String.valueOf(entry.getValue()).replace("\"", "\\\"")).append("\"");
            }
            if (it.hasNext()) {
                sb.append(",");
            }
        }
        sb.append("}");
        return sb.toString();
    }

    private String required(String key) {
        String value = bizConfigService.selectConfigByKey(key);
        if (isBlank(value)) {
            throw new ServiceException("支付配置缺失：" + key);
        }
        return value.trim();
    }

    private String optional(String key) {
        String value = bizConfigService.selectConfigByKey(key);
        return value == null ? null : value.trim();
    }

    private String trimRightSlash(String value) {
        if (value == null) {
            return null;
        }
        String s = value.trim();
        while (s.endsWith("/")) {
            s = s.substring(0, s.length() - 1);
        }
        return s;
    }

    private String firstNonBlank(String... values) {
        if (values == null) {
            return null;
        }
        for (String value : values) {
            if (!isBlank(value)) {
                return value.trim();
            }
        }
        return null;
    }

    private String defaultString(String value, String defaultValue) {
        return value == null ? defaultValue : value;
    }

    private boolean isBlank(String value) {
        return value == null || value.trim().isEmpty();
    }
}