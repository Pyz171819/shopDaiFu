package com.shop.biz.controller;

import com.shop.biz.domain.vo.WechatJsapiSignatureVo;
import com.shop.biz.service.IWechatJsapiService;
import com.shop.common.core.controller.BaseController;
import com.shop.common.core.domain.AjaxResult;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/biz/wechat")
public class WechatJsapiController extends BaseController {

    @Autowired
    private IWechatJsapiService wechatJsapiService;

    @GetMapping("/jsapi/signature")
    public AjaxResult getSignature(@RequestParam("url") String url) {
        WechatJsapiSignatureVo vo = wechatJsapiService.getJsapiSignature(url);
        return AjaxResult.success(vo);
    }
}