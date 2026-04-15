package com.shop.biz.domain.dto;

import java.io.Serializable;

public class BizConfigSaveDto implements Serializable {

    private static final long serialVersionUID = 1L;

    /**
     * 配置键
     */
    private String key;

    /**
     * 配置值
     */
    private String value;

    public String getKey() {
        return key;
    }

    public void setKey(String key) {
        this.key = key;
    }

    public String getValue() {
        return value;
    }

    public void setValue(String value) {
        this.value = value;
    }
}