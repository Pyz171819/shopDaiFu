package com.shop.biz.service.impl;

import com.shop.biz.mapper.BizConfigMapper;
import com.shop.biz.service.IBizConfigService;
import com.shop.common.exception.ServiceException;
import org.apache.commons.lang3.StringUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.LinkedHashMap;
import java.util.Map;
import java.util.Set;
import java.util.LinkedHashSet;
import java.util.Arrays;

@Service
public class BizConfigServiceImpl implements IBizConfigService {

    /**
     * 第一版只开放这 5 个通用配置
     */
    private static final Set<String> COMMON_KEYS = new LinkedHashSet<>(Arrays.asList(
            "site_notice",
            "show_notice",
            "show_slides",
            "commission_rate",
            "daifu_switch"
    ));

    @Autowired
    private BizConfigMapper bizConfigMapper;

    @Override
    public Map<String, String> getCommonConfigs() {
        Map<String, String> result = new LinkedHashMap<>();
        for (String key : COMMON_KEYS) {
            String value = bizConfigMapper.selectValueByKey(key);
            result.put(key, value == null ? "" : value);
        }
        return result;
    }

    @Override
    @Transactional(rollbackFor = Exception.class)
    public void saveValue(String key, String value) {
        validateKey(key);
        saveOrUpdate(key, value);
    }

    @Override
    @Transactional(rollbackFor = Exception.class)
    public void saveValues(Map<String, String> configs) {
        if (configs == null || configs.isEmpty()) {
            throw new ServiceException("保存参数不能为空");
        }

        for (Map.Entry<String, String> entry : configs.entrySet()) {
            String key = entry.getKey();
            String value = entry.getValue();
            validateKey(key);
            saveOrUpdate(key, value);
        }
    }

    @Override
    public String selectConfigByKey(String key) {
        return bizConfigMapper.selectValueByKey(key);
    }

    private void validateKey(String key) {
        if (StringUtils.isBlank(key)) {
            throw new ServiceException("配置键不能为空");
        }
        if (!COMMON_KEYS.contains(key)) {
            throw new ServiceException("该配置项不允许修改：" + key);
        }
    }

    private void saveOrUpdate(String key, String value) {
        String finalKey = key.trim();
        String finalValue = value == null ? "" : value.trim();

        int count = bizConfigMapper.countByKey(finalKey);
        if (count > 0) {
            bizConfigMapper.updateConfigByKey(finalKey, finalValue);
        } else {
            bizConfigMapper.insertConfig(finalKey, finalValue);
        }
    }

}