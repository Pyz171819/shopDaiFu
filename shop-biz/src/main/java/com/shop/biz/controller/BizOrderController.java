package com.shop.biz.controller;

import com.shop.biz.domain.BizOrder;
import com.shop.biz.domain.dto.OrderCreateDto;
import com.shop.biz.domain.pay.BizOrderPayReq;
import com.shop.biz.domain.pay.BizOrderPayResp;
import com.shop.biz.service.IBizOrderService;
import com.shop.common.annotation.Log;
import com.shop.common.core.controller.BaseController;
import com.shop.common.core.domain.AjaxResult;
import com.shop.common.core.page.TableDataInfo;
import com.shop.common.enums.BusinessType;
import com.shop.common.exception.ServiceException;
import com.shop.common.utils.poi.ExcelUtil;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/biz/order")
public class BizOrderController extends BaseController {

    @Autowired
    private IBizOrderService bizOrderService;



    /**
     * 查询订单列表
     */
    @PreAuthorize("@ss.hasPermi('system:orders:list')")
    @GetMapping("/listByRY")
    public TableDataInfo list(BizOrder bizOrder)
    {
        startPage();
        List<BizOrder> list = bizOrderService.selectBizOrderList(bizOrder);
        return getDataTable(list);
    }

    /**
     * 导出订单列表
     */
    @PreAuthorize("@ss.hasPermi('system:orders:export')")
    @Log(title = "订单", businessType = BusinessType.EXPORT)
    @PostMapping("/exportByRY")
    public void export(HttpServletResponse response, BizOrder bizOrder)
    {
        List<BizOrder> list = bizOrderService.selectBizOrderList(bizOrder);
        ExcelUtil<BizOrder> util = new ExcelUtil<BizOrder>(BizOrder.class);
        util.exportExcel(response, list, "订单数据");
    }

    /**
     * 获取订单详细信息
     */
    @PreAuthorize("@ss.hasPermi('system:orders:query')")
    @GetMapping(value = "/ByRY/{id}")
    public AjaxResult getInfo(@PathVariable("id") String id)
    {
        return success(bizOrderService.selectBizOrderById(Long.valueOf(id)));
    }

    /**
     * 新增订单
     */
    @PreAuthorize("@ss.hasPermi('system:orders:add')")
    @Log(title = "订单", businessType = BusinessType.INSERT)
    @PostMapping("/ByRY")
    public AjaxResult add(@RequestBody BizOrder bizOrder)
    {
        return toAjax(bizOrderService.insertBizOrder(bizOrder));
    }

    /**
     * 修改订单
     */
    @PreAuthorize("@ss.hasPermi('system:orders:edit')")
    @Log(title = "订单", businessType = BusinessType.UPDATE)
    @PutMapping("/ByRY")
    public AjaxResult edit(@RequestBody BizOrder bizOrder)
    {
        return toAjax(bizOrderService.updateBizOrder(bizOrder));
    }

    /**
     * 删除订单
     */
    @PreAuthorize("@ss.hasPermi('system:orders:remove')")
    @Log(title = "订单", businessType = BusinessType.DELETE)
    @DeleteMapping("/ByRY/{ids}")
    public AjaxResult remove(@PathVariable String[] ids)
    {
        return toAjax(bizOrderService.deleteBizOrderByIds(ids));
    }
    
    
    
//    ===================================================================================

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


    //根据支付状态查询订单信息
    @GetMapping("/queryByStatus")
    public AjaxResult queryByStatus(BizOrder  order) {
        List<BizOrder> list = bizOrderService.queryByStatus(order);
        return AjaxResult.success(list);
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

    /**
     * 微信支付回调通知
     * 实际对外访问地址：
     * /api/biz/order/pay/notify
     */
    @PostMapping(value = "/pay/notify", produces = "application/xml;charset=UTF-8")
    @ResponseBody
    public String payNotify(HttpServletRequest request) {
        return bizOrderService.handleWxPayNotify(request);
    }


    //查询今天消费流水
    @GetMapping("/queryTodayXF")
    public AjaxResult queryTodayXF(BizOrder  order) {
        return AjaxResult.success(bizOrderService.queryTodayXF(order));
    }

    //删除订单信息
    @DeleteMapping("/deleteOrder")
    public AjaxResult deleteOrder(@RequestParam("outTradeNo") String outTradeNo) {
        if (outTradeNo == null || outTradeNo.trim().isEmpty()) {
            throw new ServiceException("商户订单号不能为空");
        }
        return toAjax(bizOrderService.deleteOrder(outTradeNo));
    }
}