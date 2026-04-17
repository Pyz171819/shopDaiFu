package com.shop.biz.mapper;

import com.shop.biz.domain.BizOrder;
import org.apache.ibatis.annotations.Param;

import java.util.List;

public interface BizOrderMapper {

    int insertBizOrder(BizOrder order);

    BizOrder selectBizOrderById(@Param("id") Long id);

    BizOrder selectBizOrderByOutTradeNo(@Param("outTradeNo") String outTradeNo);

    int countByOutTradeNo(@Param("outTradeNo") String outTradeNo);

    List<BizOrder> queryByStatus(BizOrder  order);

}