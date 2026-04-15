package com.shop.biz.service;

import com.shop.biz.domain.dto.PayChannelConfigDto;

public interface IBizPayConfigService {

    /**
     * 查询支付配置
     */
    PayChannelConfigDto getPayConfig();

    /**
     * 保存支付配置
     */
    void savePayConfig(PayChannelConfigDto dto);
}