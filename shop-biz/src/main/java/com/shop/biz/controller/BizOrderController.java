package com.shop.biz.controller;

import com.shop.biz.domain.BizOrder;
import com.shop.biz.domain.dto.OrderCreateDto;
import com.shop.biz.domain.pay.BizOrderPayReq;
import com.shop.biz.domain.pay.BizOrderPayResp;
import com.shop.biz.service.IBizOrderService;
import com.shop.common.core.domain.AjaxResult;
import jakarta.servlet.http.HttpServletRequest;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/biz/order")
public class BizOrderController {

    @Autowired
    private IBizOrderService bizOrderService;

    /**
     * 创建订单
     */
    @PostMapping("/create")
    public AjaxResult create(@RequestBody OrderCreateDto dto) {
        if (dto == null) {
            return AjaxResult.error("请求参数不能为空");
        }
        BizOrder order = bizOrderService.createOrder(dto);
        return AjaxResult.success(order);
    }

    /**
     * 根据订单ID查询详情
     */
    @GetMapping("/info/{id}")
    public AjaxResult info(@PathVariable("id") Long id) {
        return AjaxResult.success(bizOrderService.getOrderById(id));
    }

    /**
     * 根据商户订单号查询详情
     */
    @GetMapping("/query")
    public AjaxResult query(@RequestParam("outTradeNo") String outTradeNo) {
        return AjaxResult.success(bizOrderService.getOrderByOutTradeNo(outTradeNo));
    }

    /**
     * 订单支付
     */
    @PostMapping("/pay")
    public AjaxResult pay(@RequestBody BizOrderPayReq req, HttpServletRequest request) {
        BizOrderPayResp resp = bizOrderService.pay(req, request);
        return AjaxResult.success(resp);
    }
}