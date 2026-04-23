package com.shop.biz.task;

import com.shop.biz.domain.BizOrder;
import com.shop.biz.service.IBizOrderService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Component;

import java.util.List;

@Component("shopTask")
public class ShopTask {
    @Autowired
    private IBizOrderService bizOrderService;

    //根据过期时间定时更新订单状态
    public void updateOrderStatus() {
        // 直接更新：状态=待支付 且 已过期 的订单
        // 一次性批量更新，性能极高
        bizOrderService.updateExpiredOrderStatus();
    }
}
