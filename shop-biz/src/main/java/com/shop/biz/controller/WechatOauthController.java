package com.shop.biz.controller;

import com.shop.biz.service.IWechatOauthService;
import com.shop.common.exception.ServiceException;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import org.apache.commons.lang3.StringUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Controller;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestParam;

import java.io.IOException;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;

@Controller
public class WechatOauthController {

    private static final String CURRENT_WECHAT_OPENID = "CURRENT_WECHAT_OPENID";

    @Autowired
    private IWechatOauthService wechatOauthService;

    @GetMapping("/wechat/oauth/start")
    public void startOauth(@RequestParam("returnUrl") String returnUrl,
                           HttpServletRequest request,
                           HttpServletResponse response) throws IOException {

        System.out.println("oauth start sessionId=" + request.getSession().getId());
        System.out.println("oauth start returnUrl=" + returnUrl);

        if (StringUtils.isBlank(returnUrl)) {
            throw new ServiceException("returnUrl不能为空");
        }

        String callbackUrl = "https://dfsccsxt.meituandaif.cn"
                + "/api/wechat/oauth/callback?returnUrl="
                + URLEncoder.encode(returnUrl, StandardCharsets.UTF_8);

        String authorizeUrl = wechatOauthService.buildAuthorizeUrl(callbackUrl);
        response.sendRedirect(authorizeUrl);
    }

    @GetMapping("/wechat/oauth/callback")
    public void oauthCallback(@RequestParam("code") String code,
                              @RequestParam("returnUrl") String returnUrl,
                              HttpServletRequest request,
                              HttpServletResponse response) throws IOException {

        System.out.println("oauth callback code=" + code);
        System.out.println("oauth callback sessionId(before save)=" + request.getSession().getId());
        System.out.println("oauth callback returnUrl=" + returnUrl);

        String openId = wechatOauthService.getOpenIdByCode(code);
        System.out.println("oauth callback openId=" + openId);

        request.getSession().setAttribute(CURRENT_WECHAT_OPENID, openId);

        System.out.println("oauth callback saved openId="
                + request.getSession().getAttribute(CURRENT_WECHAT_OPENID));

        response.sendRedirect(returnUrl);
    }

    private String buildBaseUrl(HttpServletRequest request) {
        String scheme = getHeaderFirst(request, "X-Forwarded-Proto");
        if (StringUtils.isBlank(scheme)) {
            scheme = request.getScheme();
        }

        String host = getHeaderFirst(request, "X-Forwarded-Host");
        if (StringUtils.isBlank(host)) {
            host = request.getServerName();
            int port = request.getServerPort();
            if (!(("http".equalsIgnoreCase(scheme) && port == 80)
                    || ("https".equalsIgnoreCase(scheme) && port == 443))) {
                host = host + ":" + port;
            }
        }

        return scheme + "://" + host;
    }

    private String getHeaderFirst(HttpServletRequest request, String headerName) {
        String value = request.getHeader(headerName);
        if (StringUtils.isBlank(value)) {
            return null;
        }
        if (value.contains(",")) {
            return value.split(",")[0].trim();
        }
        return value.trim();
    }
}