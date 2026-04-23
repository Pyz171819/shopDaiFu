package com.shop.biz.controller;

import com.fasterxml.jackson.core.type.TypeReference;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.shop.biz.domain.BizOrder;
import com.shop.biz.domain.BizShareCardConfig;
import com.shop.biz.service.IBizOrderService;
import com.shop.biz.service.IBizShareCardConfigService;
import com.shop.common.core.domain.entity.SysUser;
import com.shop.common.exception.ServiceException;
import com.shop.system.service.ISysUserService;
import jakarta.servlet.http.HttpServletRequest;
import org.apache.commons.lang3.StringUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestParam;

import java.math.BigDecimal;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;

@Controller
public class ShareCashierController {

    private static final String CURRENT_WECHAT_OPENID = "CURRENT_WECHAT_OPENID";
    private static final String BASE_URL = "https://dfsccsxt.meituandaif.cn";

    @Autowired
    private IBizOrderService bizOrderService;

    @Autowired
    private ISysUserService usersService;

    @Autowired
    private IBizShareCardConfigService shareCardConfigService;

    private final ObjectMapper objectMapper = new ObjectMapper();

    @GetMapping("/share/cashier")
    public String shareCashier(@RequestParam("outTradeNo") String outTradeNo,
                               @RequestParam(value = "from", required = false) String from,
                               HttpServletRequest request,
                               Model model) {

        Object currentOpenId = request.getSession().getAttribute(CURRENT_WECHAT_OPENID);

        if (isWechatBrowser(request) && currentOpenId == null) {
            String currentUrl = BASE_URL + "/share/cashier?outTradeNo="
                    + URLEncoder.encode(outTradeNo, StandardCharsets.UTF_8)
                    + "&from=" + URLEncoder.encode(StringUtils.defaultString(from, "share"), StandardCharsets.UTF_8);

            String redirectUrl = "/api/wechat/oauth/start?returnUrl="
                    + URLEncoder.encode(currentUrl, StandardCharsets.UTF_8);
            return "redirect:" + redirectUrl;
        }

        BizOrder order = bizOrderService.getOrderByOutTradeNo(outTradeNo);
        if (order == null) {
            throw new ServiceException("订单不存在");
        }

        // 根据订单 tpl 决定最终模板
        String tpl = StringUtils.defaultIfBlank(order.getTpl(), "cashier").trim();
        // 防止非法路径
        if (!tpl.matches("^[a-zA-Z0-9_-]+$")) {
            tpl = "cashier";
        }

        List<Map<String, Object>> orderItems = parseItems(order.getItems());

        boolean isPaid = "1".equals(String.valueOf(order.getStatus()));
        long remainingSeconds = 0L;
        long expireTimestamp = 0L;

        if (!isPaid && order.getExpireTime() != null) {
            expireTimestamp = order.getExpireTime().getTime();
            remainingSeconds = Math.max(0L, (expireTimestamp - System.currentTimeMillis()) / 1000);
        }

        String shareUrl = BASE_URL + "/share/cashier?outTradeNo="
                + URLEncoder.encode(outTradeNo, StandardCharsets.UTF_8) + "&from=share";

        String shareImage = buildShareImage(BASE_URL, orderItems);

        model.addAttribute("order", order);
        model.addAttribute("orderItems", orderItems);
        model.addAttribute("isPaid", isPaid);
        model.addAttribute("remainingSeconds", remainingSeconds);
        model.addAttribute("expireTimestamp", expireTimestamp);
        model.addAttribute("shareUrl", shareUrl);
        model.addAttribute("from", StringUtils.defaultString(from));
        model.addAttribute("tpl", tpl);

        SysUser sysUser = usersService.selectUserById(Long.valueOf(order.getCreateBy()));
        BizShareCardConfig shareCardConfig = shareCardConfigService.selectBizShareCardConfigBytpl(order.getTpl());

        if (sysUser != null) {
            model.addAttribute("userNick", sysUser.getNickName());
            model.addAttribute("userAvatar", BASE_URL + "/api" + sysUser.getAvatar());
        }
        if (shareCardConfig != null) {
            model.addAttribute("userSlogan", shareCardConfig.getShareDesc());
            model.addAttribute("shareTitle", shareCardConfig.getShareTitle());
            model.addAttribute("shareDesc", shareCardConfig.getShareDesc());

            if ("1".equals(shareCardConfig.getUseProductImage())) {
                model.addAttribute("shareImage", shareImage);
            } else {
                model.addAttribute("shareImage", BASE_URL + "/api" + shareCardConfig.getShareImage());
            }
        } else {
            // 兜底，避免空指针
            model.addAttribute("userSlogan", "Hi~你和我的距离只差一顿外卖~");
            model.addAttribute("shareTitle", StringUtils.defaultIfBlank(order.getOrderName(), "请帮我代付"));
            model.addAttribute("shareDesc", "请帮我代付");
            model.addAttribute("shareImage", shareImage);
        }
        return "share/" + tpl;
    }

    private boolean isWechatBrowser(HttpServletRequest request) {
        String ua = request.getHeader("User-Agent");
        return StringUtils.isNotBlank(ua) && ua.toLowerCase().contains("micromessenger");
    }

    private String buildCurrentUrl(HttpServletRequest request) {
        StringBuilder sb = new StringBuilder(request.getRequestURL());
        if (StringUtils.isNotBlank(request.getQueryString())) {
            sb.append("?").append(request.getQueryString());
        }
        return sb.toString();
    }

    private List<Map<String, Object>> parseItems(String items) {
        if (StringUtils.isBlank(items)) {
            return new ArrayList<>();
        }
        try {
            return objectMapper.readValue(items, new TypeReference<List<Map<String, Object>>>() {});
        } catch (Exception e) {
            return new ArrayList<>();
        }
    }

    private String buildShareImage(String baseUrl, List<Map<String, Object>> orderItems) {
        if (orderItems != null && !orderItems.isEmpty()) {
            Object imageObj = orderItems.get(0).get("image");
            if (imageObj != null) {
                String image = String.valueOf(imageObj);
                if (StringUtils.isBlank(image)) {
                    return baseUrl + "/logo.png";
                }
                if (image.startsWith("http://") || image.startsWith("https://")) {
                    return image;
                }
                return baseUrl + "/api" + image;
            }
        }
        return baseUrl + "/logo.png";
    }

    private String formatMoney(BigDecimal money) {
        return money == null ? "0.00" : money.stripTrailingZeros().toPlainString();
    }
}