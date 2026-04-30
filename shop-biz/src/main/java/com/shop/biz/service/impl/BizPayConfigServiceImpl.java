package com.shop.biz.service.impl;

import com.shop.biz.domain.dto.PayChannelConfigDto;
import com.shop.biz.mapper.BizConfigMapper;
import com.shop.biz.service.IBizPayConfigService;
import com.shop.common.exception.ServiceException;
import org.apache.commons.lang3.StringUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

@Service
public class BizPayConfigServiceImpl implements IBizPayConfigService {

    private static final String CHANNEL_WXPAY = "wxpay";
    private static final String CHANNEL_EPAY = "epay";
    private static final String CHANNEL_MANUAL = "manual";
    private static final String CHANNEL_YSM = "ysm";

    @Autowired
    private BizConfigMapper bizConfigMapper;

    @Override
    public PayChannelConfigDto getPayConfig() {
        PayChannelConfigDto dto = new PayChannelConfigDto();

        dto.setPayChannel(defaultIfBlank(getValue("pay_channel"), CHANNEL_WXPAY));

        dto.setWxpayAppid(getValue("wxpay_appid"));
        dto.setWxpaySecret(getValue("wxpay_secret"));
        dto.setWxpayMchid(getValue("wxpay_mchid"));
        dto.setWxpayKey(getValue("wxpay_key"));

        dto.setEpayApi(getValue("epay_api"));
        dto.setEpayId(getValue("epay_id"));
        dto.setEpayKey(getValue("epay_key"));

        dto.setManualQrcode(getValue("pay_manual_qrcode"));

        dto.setYsmApi(getValue("ysm_api"));
        dto.setYsmId(getValue("ysm_id"));
        dto.setYsmKey(getValue("ysm_key"));
        dto.setYsmPayType(getValue("ysm_pay_type"));

        return dto;
    }

    @Override
    @Transactional(rollbackFor = Exception.class)
    public void savePayConfig(PayChannelConfigDto dto) {
        if (dto == null) {
            throw new ServiceException("请求参数不能为空");
        }

        String payChannel = trimToEmpty(dto.getPayChannel());
        if (StringUtils.isBlank(payChannel)) {
            throw new ServiceException("请选择支付通道");
        }

        validateByChannel(payChannel, dto);

        saveOrUpdate("pay_channel", payChannel);

        switch (payChannel) {
            case CHANNEL_WXPAY:
                saveOrUpdate("wxpay_appid", dto.getWxpayAppid());
                saveOrUpdate("wxpay_secret", dto.getWxpaySecret());
                saveOrUpdate("wxpay_mchid", dto.getWxpayMchid());
                saveOrUpdate("wxpay_key", dto.getWxpayKey());
                break;

            case CHANNEL_EPAY:
                saveOrUpdate("epay_api", dto.getEpayApi());
                saveOrUpdate("epay_id", dto.getEpayId());
                saveOrUpdate("epay_key", dto.getEpayKey());
                break;

            default:
                throw new ServiceException("不支持的支付通道：" + payChannel);
        }
    }

    private void validateByChannel(String payChannel, PayChannelConfigDto dto) {
        switch (payChannel) {
            case CHANNEL_WXPAY:
                if (StringUtils.isBlank(dto.getWxpayAppid())) {
                    throw new ServiceException("请填写微信 AppID");
                }
                if (StringUtils.isBlank(dto.getWxpaySecret())) {
                    throw new ServiceException("请填写微信 AppSecret");
                }
                if (StringUtils.isBlank(dto.getWxpayMchid())) {
                    throw new ServiceException("请填写微信商户号 MCHID");
                }
                if (StringUtils.isBlank(dto.getWxpayKey())) {
                    throw new ServiceException("请填写微信 KeyV2");
                }
                break;

            case CHANNEL_EPAY:
                if (StringUtils.isBlank(dto.getEpayApi())) {
                    throw new ServiceException("请填写易支付接口地址");
                }
                if (StringUtils.isBlank(dto.getEpayId())) {
                    throw new ServiceException("请填写易支付商户ID");
                }
                if (StringUtils.isBlank(dto.getEpayKey())) {
                    throw new ServiceException("请填写易支付密钥");
                }
                break;

            default:
                throw new ServiceException("未知支付通道：" + payChannel);
        }
    }

    private void saveOrUpdate(String key, String value) {
        String finalKey = trimToEmpty(key);
        String finalValue = trimToEmpty(value);

        int count = bizConfigMapper.countByKey(finalKey);
        if (count > 0) {
            bizConfigMapper.updateConfigByKey(finalKey, finalValue);
        } else {
            bizConfigMapper.insertConfig(finalKey, finalValue);
        }
    }

    private String getValue(String key) {
        String value = bizConfigMapper.selectValueByKey(key);
        return trimToEmpty(value);
    }

    private String trimToEmpty(String str) {
        return str == null ? "" : str.trim();
    }

    private String defaultIfBlank(String value, String defaultValue) {
        return StringUtils.isBlank(value) ? defaultValue : value;
    }
}