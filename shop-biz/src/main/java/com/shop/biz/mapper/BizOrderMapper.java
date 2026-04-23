package com.shop.biz.mapper;

import com.shop.biz.domain.BizOrder;
import com.shop.biz.domain.vo.DashboardRevenuePointVo;
import org.apache.ibatis.annotations.Param;

import java.math.BigDecimal;
import java.util.Date;
import java.util.List;

public interface BizOrderMapper {



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
    public int insertBizOrder2(BizOrder bizOrder);

    /**
     * 修改订单
     *
     * @param bizOrder 订单
     * @return 结果
     */
    public int updateBizOrder2(BizOrder bizOrder);

    /**
     * 删除订单
     *
     * @param id 订单主键
     * @return 结果
     */
    public int deleteBizOrderById(String id);

    /**
     * 批量删除订单
     *
     * @param ids 需要删除的数据主键集合
     * @return 结果
     */
    public int deleteBizOrderByIds(String[] ids);

//    =================================================================================

    int insertBizOrder(BizOrder order);

    BizOrder selectBizOrderById(@Param("id") Long id);
    /**
     * 查询订单
     *
     * @param id 订单主键
     * @return 订单
     */
    public BizOrder selectBizOrderById2(Long id);

    BizOrder selectBizOrderByOutTradeNo(@Param("outTradeNo") String outTradeNo);

    int countByOutTradeNo(@Param("outTradeNo") String outTradeNo);

    List<BizOrder> queryByStatus(BizOrder  order);

    BigDecimal queryTodayXF(BizOrder order);

    int deleteBizOrderByOutTradeNo(String outTradeNo);

    int updateBizOrder(BizOrder order);

    void updateExpiredOrderStatus();

    Long countOrdersBetween(@Param("startTime") Date startTime, @Param("endTime") Date endTime, @Param("status") String status);

    Long countOrdersByStatus(@Param("status") String status);

    BigDecimal sumPaidAmountBetween(@Param("startTime") Date startTime, @Param("endTime") Date endTime);

    List<DashboardRevenuePointVo> selectRevenueTrend(@Param("startTime") Date startTime, @Param("endTime") Date endTime);

    List<BizOrder> selectPaidOrdersForDashboard();

}
