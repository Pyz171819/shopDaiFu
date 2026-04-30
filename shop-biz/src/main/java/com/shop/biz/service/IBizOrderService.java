package com.shop.biz.service;

import com.shop.biz.domain.BizOrder;
import com.shop.biz.domain.dto.OrderCreateDto;
import com.shop.biz.domain.pay.BizOrderPayReq;
import com.shop.biz.domain.pay.BizOrderPayResp;
import jakarta.servlet.http.HttpServletRequest;

import java.math.BigDecimal;
import java.util.List;

public interface IBizOrderService {

    /**
     * 查询订单
     *
     * @param id 订单主键
     * @return 订单
     */
    public BizOrder selectBizOrderById(Long id);

    /**
     * 查询订单列表
     *
     * @param bizOrder 订单
     * @return 订单集合
     */
    public List<BizOrder> selectBizOrderList(BizOrder bizOrder);

    /**
     * 新增订单
     *
     * @param bizOrder 订单
     * @return 结果
     */
    public int insertBizOrder(BizOrder bizOrder);

    /**
     * 修改订单
     *
     * @param bizOrder 订单
     * @return 结果
     */
    public int updateBizOrder(BizOrder bizOrder);

    /**
     * 批量删除订单
     *
     * @param ids 需要删除的订单主键集合
     * @return 结果
     */
    public int deleteBizOrderByIds(String[] ids);

    /**
     * 删除订单信息
     *
     * @param id 订单主键
     * @return 结果
     */
    public int deleteBizOrderById(String id);
    
    
//    ==================================================================================
    
    /**
     * 创建订单
     */
    BizOrder createOrder(OrderCreateDto dto);

    /**
     * 根据ID查询订单
     */
    BizOrder getOrderById(Long id);

    /**
     * 根据商户订单号查询订单
     */
    BizOrder getOrderByOutTradeNo(String outTradeNo);

    BizOrderPayResp pay(BizOrderPayReq req, HttpServletRequest request);

    /**
     * 微信支付回调处理
     */
    String handleWxPayNotify(HttpServletRequest request);

    String handleEpayNotify(HttpServletRequest request);

    List<BizOrder> queryByStatus(BizOrder  order);

    BigDecimal queryTodayXF(BizOrder  order);

    int deleteOrder(String outTradeNo);

    void updateExpiredOrderStatus();

}
