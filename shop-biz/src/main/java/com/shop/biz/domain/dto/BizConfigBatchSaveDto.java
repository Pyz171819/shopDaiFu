package com.shop.biz.domain.dto;

import java.io.Serializable;
import java.util.Map;

public class BizConfigBatchSaveDto implements Serializable {

    private static final long serialVersionUID = 1L;

    /**
     * 批量配置
     * 例如：
     * {
     *   "site_notice": "欢迎使用商城",
     *   "show_notice": "1"
     * }
     */
    private Map<String, String> configs;

    public Map<String, String> getConfigs() {
        return configs;
    }

    public void setConfigs(Map<String, String> configs) {
        this.configs = configs;
    }
}