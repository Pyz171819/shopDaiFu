package com.shop.biz.controller;

import com.shop.biz.domain.dto.BizConfigBatchSaveDto;
import com.shop.biz.domain.dto.BizConfigSaveDto;
import com.shop.biz.service.IBizConfigService;
import com.shop.common.core.domain.AjaxResult;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.web.bind.annotation.*;

import java.util.Map;

@RestController
@RequestMapping("/biz/config")
public class BizConfigController {

    @Autowired
    private IBizConfigService bizConfigService;

    /**
     * 查询全部通用配置
     */
    @GetMapping("/common")
    public AjaxResult common() {
        Map<String, String> data = bizConfigService.getCommonConfigs();
        return AjaxResult.success(data);
    }

    /**
     * 保存单个通用配置
     */
    @PostMapping("/save")
    public AjaxResult save(@RequestBody BizConfigSaveDto saveDto) {
        if (saveDto == null) {
            return AjaxResult.error("请求参数不能为空");
        }
        bizConfigService.saveValue(saveDto.getKey(), saveDto.getValue());
        return AjaxResult.success("保存成功");
    }

    /**
     * 批量保存通用配置
     */
    @PostMapping("/saveBatch")
    public AjaxResult saveBatch(@RequestBody BizConfigBatchSaveDto batchSaveDto) {
        if (batchSaveDto == null || batchSaveDto.getConfigs() == null || batchSaveDto.getConfigs().isEmpty()) {
            return AjaxResult.error("请求参数不能为空");
        }
        bizConfigService.saveValues(batchSaveDto.getConfigs());
        return AjaxResult.success("批量保存成功");
    }
}