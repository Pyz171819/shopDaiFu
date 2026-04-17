package com.shop.biz.service.impl;

import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.shop.biz.domain.BizOrder;
import com.shop.biz.domain.BizProductSimple;
import com.shop.biz.domain.dto.OrderCreateDto;
import com.shop.biz.domain.dto.OrderItemDto;
import com.shop.biz.domain.pay.BizOrderPayReq;
import com.shop.biz.domain.pay.BizOrderPayResp;
import com.shop.biz.mapper.BizConfigMapper;
import com.shop.biz.mapper.BizOrderMapper;
import com.shop.biz.mapper.BizProductsMapper;
import com.shop.biz.service.IBizOrderService;
import com.shop.biz.utils.WechatPayGateway;
import com.shop.common.exception.ServiceException;
import com.shop.common.utils.SecurityUtils;
import jakarta.servlet.http.HttpServletRequest;
import org.apache.commons.lang3.StringUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.math.BigDecimal;
import java.security.SecureRandom;
import java.text.SimpleDateFormat;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.*;

@Service
public class BizOrderServiceImpl implements IBizOrderService {

    private static final String STATUS_UNPAID = "0";
    private static final String DEFAULT_TEMPLATE = "default";
    private static final String DEFAULT_TPL = "cashier";
    private static final String DEFAULT_PAY_CHANNEL = "wxpay";

    @Autowired
    private BizOrderMapper bizOrderMapper;

    @Autowired
    private BizProductsMapper bizProductMapper;

    @Autowired
    private BizConfigMapper bizConfigMapper;

    @Autowired
    private WechatPayGateway wechatPayGateway;
//    @Autowired
//    private ObjectMapper objectMapper;
    private final ObjectMapper objectMapper = new ObjectMapper();

    private final SecureRandom secureRandom = new SecureRandom();

    @Override
    @Transactional(rollbackFor = Exception.class)
    public BizOrder createOrder(OrderCreateDto dto) {
        if (dto == null) {
            throw new ServiceException("请求参数不能为空");
        }
        if (dto.getItems() == null || dto.getItems().isEmpty()) {
            throw new ServiceException("订单商品不能为空");
        }

        List<OrderItemDto> normalizedItems = normalizeItems(dto.getItems());
        if (normalizedItems.isEmpty()) {
            throw new ServiceException("订单商品不能为空");
        }

        List<Map<String, Object>> snapshotItems = new ArrayList<>();
        BigDecimal totalMoney = BigDecimal.ZERO;
        Long ownerUserId = null;
        Integer singleProductId = null;
        String firstProductName = null;

        for (int i = 0; i < normalizedItems.size(); i++) {
            OrderItemDto orderItem = normalizedItems.get(i);

            if (orderItem.getProductId() == null) {
                throw new ServiceException("商品ID不能为空");
            }
            if (orderItem.getQuantity() == null || orderItem.getQuantity() <= 0) {
                throw new ServiceException("商品数量必须大于0");
            }

            BizProductSimple product = bizProductMapper.selectSimpleProductById(orderItem.getProductId());
            if (product == null) {
                throw new ServiceException("商品不存在，商品ID：" + orderItem.getProductId());
            }
            if (product.getAuditStatus() == null || product.getAuditStatus() != 1) {
                throw new ServiceException("商品未审核通过，暂不能下单：" + product.getName());
            }
            if (product.getPrice() == null || product.getPrice().compareTo(BigDecimal.ZERO) <= 0) {
                throw new ServiceException("商品价格异常：" + product.getName());
            }

            // 第一版建议同一订单下商品归属同一个用户，否则后续结算会混乱
            if (ownerUserId == null) {
                ownerUserId = product.getUserId();
            } else if (!Objects.equals(ownerUserId, product.getUserId())) {
                throw new ServiceException("暂不支持不同用户的商品合并下单");
            }

            if (i == 0) {
                firstProductName = defaultIfBlank(product.getName(), "商品订单");
            }

            if (normalizedItems.size() == 1) {
                singleProductId = product.getId();
            }

            BigDecimal lineTotal = product.getPrice().multiply(BigDecimal.valueOf(orderItem.getQuantity()));
            totalMoney = totalMoney.add(lineTotal);

            Map<String, Object> snapshot = new LinkedHashMap<>();
            snapshot.put("id", product.getId());
            snapshot.put("name", product.getName());
            snapshot.put("price", product.getPrice());
            snapshot.put("quantity", orderItem.getQuantity());
            snapshot.put("totalAmount", lineTotal);
            snapshot.put("image", product.getImage());
            snapshot.put("shopName", product.getShopName());

            snapshotItems.add(snapshot);
        }

        BizOrder order = new BizOrder();
        order.setOutTradeNo(generateOutTradeNo());
        order.setProductId(singleProductId); // 单商品写 product_id，多商品留空
        order.setMoney(totalMoney);
        order.setStatus(STATUS_UNPAID);
        order.setPayType(null);
        order.setType(getCurrentPayChannel());
        order.setOrderName(buildOrderName(snapshotItems, firstProductName));
        order.setTemplate(defaultIfBlank(dto.getTemplate(), DEFAULT_TEMPLATE));
        order.setTpl(defaultIfBlank(dto.getTpl(), DEFAULT_TPL));
        order.setItems(toJson(snapshotItems));
        order.setExpireTime(buildExpireTime(15));
        order.setOrderCreateUserId(ownerUserId);
        order.setCreateBy(String.valueOf(SecurityUtils.getUserId()));
        order.setDelFlag("0");

        bizOrderMapper.insertBizOrder(order);
        return bizOrderMapper.selectBizOrderById(order.getId());
    }

    @Override
    public BizOrder getOrderById(Long id) {
        if (id == null) {
            throw new ServiceException("订单ID不能为空");
        }
        BizOrder order = bizOrderMapper.selectBizOrderById(id);
        if (order == null) {
            throw new ServiceException("订单不存在");
        }
        return order;
    }

    @Override
    public BizOrder getOrderByOutTradeNo(String outTradeNo) {
        if (StringUtils.isBlank(outTradeNo)) {
            throw new ServiceException("商户订单号不能为空");
        }
        BizOrder order = bizOrderMapper.selectBizOrderByOutTradeNo(outTradeNo.trim());
        if (order == null) {
            throw new ServiceException("订单不存在");
        }
        return order;
    }

    @Override
    public BizOrderPayResp pay(BizOrderPayReq req, HttpServletRequest request) {
        if (req == null || req.getTradeNo() == null || req.getTradeNo().trim().isEmpty()) {
            throw new ServiceException("订单号不能为空");
        }

        BizOrder order = bizOrderMapper.selectBizOrderByOutTradeNo(req.getTradeNo());
        if (order == null || "1".equals(order.getDelFlag())) {
            throw new ServiceException("订单不存在");
        }

        if ("1".equals(order.getStatus())) {
            throw new ServiceException("订单已支付，请勿重复支付");
        }

        if ("2".equals(order.getStatus())) {
            throw new ServiceException("订单已关闭");
        }

        if ("3".equals(order.getStatus())) {
            throw new ServiceException("订单已退款");
        }

        if (order.getExpireTime() != null && LocalDateTime.now().isAfter(toLocalDateTime(order.getExpireTime()))) {
            throw new ServiceException("订单已过期");
        }

        String clientType = normalizeClientType(req.getClientType(), request);

        switch (clientType) {
            case "pc":
                return buildNativePay(order);

            case "wechat_h5":
                return buildJsapiPay(order, req);

            case "mobile_h5":
                String clientIp = getClientIp(request);
                return buildH5Pay(order, clientIp);

            default:
                throw new ServiceException("不支持的支付环境");
        }
    }

    @Override
    public List<BizOrder> queryByStatus(BizOrder  order) {
         return bizOrderMapper.queryByStatus(order);
    }

    private String getClientIp(HttpServletRequest request) {
        if (request == null) {
            return "127.0.0.1";
        }

        String[] headerNames = {
                "X-Forwarded-For",
                "Proxy-Client-IP",
                "WL-Proxy-Client-IP",
                "HTTP_X_FORWARDED_FOR",
                "HTTP_X_FORWARDED",
                "HTTP_X_CLUSTER_CLIENT_IP",
                "HTTP_CLIENT_IP",
                "HTTP_FORWARDED_FOR",
                "HTTP_FORWARDED",
                "HTTP_VIA"
        };

        for (String header : headerNames) {
            String ip = request.getHeader(header);
            if (ip != null && ip.length() != 0 && !"unknown".equalsIgnoreCase(ip)) {
                if (ip.contains(",")) {
                    return ip.split(",")[0].trim();
                }
                return ip.trim();
            }
        }

        String ip = request.getRemoteAddr();
        if (ip == null || ip.length() == 0) {
            return "127.0.0.1";
        }

        if ("0:0:0:0:0:0:0:1".equals(ip) || "::1".equals(ip)) {
            return "127.0.0.1";
        }

        return ip;
    }


    private BizOrderPayResp buildNativePay(BizOrder order) {
        String codeUrl = wechatPayGateway.nativePay(order);

        BizOrderPayResp resp = new BizOrderPayResp();
        resp.setPayScene("NATIVE");
        resp.setTradeNo(order.getOutTradeNo());
        resp.setCodeUrl(codeUrl);
        resp.setAmount(order.getMoney().toPlainString());
        resp.setExpireTime(format(order.getExpireTime()));
        return resp;
    }

    private BizOrderPayResp buildJsapiPay(BizOrder order, BizOrderPayReq req) {
        String openId = req.getOpenId();
        if (openId == null || openId.trim().isEmpty()) {
            openId = order.getOpenid();
        }
        if (openId == null || openId.trim().isEmpty()) {
            throw new ServiceException("微信内支付缺少openId");
        }

        BizOrderPayResp resp = new BizOrderPayResp();
        resp.setPayScene("JSAPI");
        resp.setTradeNo(order.getOutTradeNo());
        resp.setPayParams(wechatPayGateway.jsapiPay(order, openId));
        resp.setAmount(order.getMoney().toPlainString());
        resp.setExpireTime(format(order.getExpireTime()));
        return resp;
    }


    private BizOrderPayResp buildH5Pay(BizOrder order, String clientIp) {
        BizOrderPayResp resp = new BizOrderPayResp();
        resp.setPayScene("H5");
        resp.setTradeNo(order.getOutTradeNo());
        resp.setH5Url(wechatPayGateway.h5Pay(order, clientIp));
        resp.setAmount(order.getMoney().toPlainString());
        resp.setExpireTime(format(order.getExpireTime()));
        return resp;
    }


    private String normalizeClientType(String clientType, HttpServletRequest request) {
        if (clientType != null && !clientType.trim().isEmpty()) {
            return clientType;
        }

        String ua = request.getHeader("User-Agent");
        boolean isWechat = ua != null && ua.toLowerCase().contains("micromessenger");
        boolean isMobile = ua != null && ua.toLowerCase().matches(".*(android|iphone|ipad|mobile).*");

        if (isWechat) {
            return "wechat_h5";
        }
        if (isMobile) {
            return "mobile_h5";
        }
        return "pc";
    }

    private LocalDateTime toLocalDateTime(java.util.Date date) {
        return LocalDateTime.ofInstant(date.toInstant(), java.time.ZoneId.systemDefault());
    }

    private String format(java.util.Date date) {
        if (date == null) {
            return null;
        }
        return DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss")
                .format(toLocalDateTime(date));
    }




    /**
     * 合并重复商品，避免同一商品多条重复项
     */
    private List<OrderItemDto> normalizeItems(List<OrderItemDto> rawItems) {
        Map<Integer, Integer> merged = new LinkedHashMap<>();

        for (OrderItemDto item : rawItems) {
            if (item == null || item.getProductId() == null) {
                continue;
            }
            int quantity = item.getQuantity() == null ? 0 : item.getQuantity();
            if (quantity <= 0) {
                continue;
            }
            merged.put(item.getProductId(), merged.getOrDefault(item.getProductId(), 0) + quantity);
        }

        List<OrderItemDto> result = new ArrayList<>();
        for (Map.Entry<Integer, Integer> entry : merged.entrySet()) {
            OrderItemDto dto = new OrderItemDto();
            dto.setProductId(entry.getKey());
            dto.setQuantity(entry.getValue());
            result.add(dto);
        }
        return result;
    }

    private String getCurrentPayChannel() {
        String value = bizConfigMapper.selectValueByKey("pay_channel");
        return StringUtils.isBlank(value) ? DEFAULT_PAY_CHANNEL : value.trim();
    }

    private String buildOrderName(List<Map<String, Object>> snapshotItems, String firstProductName) {
        if (snapshotItems == null || snapshotItems.isEmpty()) {
            return "商品订单";
        }

        if (snapshotItems.size() == 1) {
            Map<String, Object> item = snapshotItems.get(0);
            Object quantity = item.get("quantity");
            return defaultIfBlank(firstProductName, "商品订单") + " x" + quantity;
        }

        return defaultIfBlank(firstProductName, "商品订单") + "等" + snapshotItems.size() + "件商品";
    }

    private Date buildExpireTime(int minutes) {
        Calendar calendar = Calendar.getInstance();
        calendar.add(Calendar.MINUTE, minutes);
        return calendar.getTime();
    }

    private String toJson(Object value) {
        try {
            return objectMapper.writeValueAsString(value);
        } catch (JsonProcessingException e) {
            throw new ServiceException("订单商品快照生成失败");
        }
    }

    private String generateOutTradeNo() {
        for (int i = 0; i < 10; i++) {
            String no = buildOutTradeNoCandidate();
            if (bizOrderMapper.countByOutTradeNo(no) == 0) {
                return no;
            }
        }
        throw new ServiceException("生成商户订单号失败，请稍后重试");
    }

    private String buildOutTradeNoCandidate() {
        String timePart = new SimpleDateFormat("yyyyMMddHHmmssSSS").format(new Date());
        int randomPart = 100000 + secureRandom.nextInt(900000);
        return timePart + randomPart;
    }

    private String defaultIfBlank(String value, String defaultValue) {
        return StringUtils.isBlank(value) ? defaultValue : value.trim();
    }
}