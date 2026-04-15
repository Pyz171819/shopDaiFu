package com.shop.biz.service;

import java.util.Map;

public interface IBizConfigService {

    /**
     * 查询全部通用配置
     */
    Map<String, String> getCommonConfigs();

    /**
     * 保存单个通用配置
     */
    void saveValue(String key, String value);
    
    /**
     * 批量保存通用配置
     */
    void saveValues(Map<String, String> configs);

    String selectConfigByKey(String key);
}