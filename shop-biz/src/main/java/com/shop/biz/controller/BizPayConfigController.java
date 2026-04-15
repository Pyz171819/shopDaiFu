package com.shop.biz.controller;

import com.shop.biz.domain.dto.PayChannelConfigDto;
import com.shop.biz.service.IBizPayConfigService;
import com.shop.common.core.domain.AjaxResult;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/biz/pay/config")
public class BizPayConfigController {

    @Autowired
    private IBizPayConfigService bizPayConfigService;

    /**
     * 获取支付配置详情
     */
    @GetMapping("/info")
    public AjaxResult info() {
        return AjaxResult.success(bizPayConfigService.getPayConfig());
    }

    /**
     * 保存支付配置
     */
    @PostMapping("/save")
    public AjaxResult save(@RequestBody PayChannelConfigDto dto) {
        if (dto == null) {
            return AjaxResult.error("请求参数不能为空");
        }
        bizPayConfigService.savePayConfig(dto);
        return AjaxResult.success("保存成功");
    }
}