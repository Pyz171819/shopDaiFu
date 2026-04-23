package com.shop.biz.controller;

import com.shop.biz.service.IBizDashboardService;
import com.shop.common.core.controller.BaseController;
import com.shop.common.core.domain.AjaxResult;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

@RestController
@RequestMapping("/biz/dashboard")
public class BizDashboardController extends BaseController {

    @Autowired
    private IBizDashboardService bizDashboardService;

    @GetMapping("/overview")
    public AjaxResult overview() {
        return success(bizDashboardService.getOverview());
    }
}
