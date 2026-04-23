package com.shop.biz.service.impl;

import java.util.List;
import com.shop.common.utils.DateUtils;
import com.shop.common.utils.SecurityUtils;
import com.shop.framework.config.SecurityConfig;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import com.shop.biz.mapper.BizShareCardConfigMapper;
import com.shop.biz.domain.BizShareCardConfig;
import com.shop.biz.service.IBizShareCardConfigService;

/**
 * 分享卡片配置Service业务层处理
 * 
 * @author shop
 * @date 2026-04-12
 */
@Service
public class BizShareCardConfigServiceImpl implements IBizShareCardConfigService 
{
    @Autowired
    private BizShareCardConfigMapper bizShareCardConfigMapper;

    /**
     * 查询分享卡片配置
     * 
     * @param id 分享卡片配置主键
     * @return 分享卡片配置
     */
    @Override
    public BizShareCardConfig selectBizShareCardConfigById(String id)
    {
        return bizShareCardConfigMapper.selectBizShareCardConfigById(id);
    }

    @Override
    public BizShareCardConfig selectBizShareCardConfigBytpl(String id)
    {
        return bizShareCardConfigMapper.selectBizShareCardConfigBytpl(id);
    }

    /**
     * 查询分享卡片配置列表
     * 
     * @param bizShareCardConfig 分享卡片配置
     * @return 分享卡片配置
     */
    @Override
    public List<BizShareCardConfig> selectBizShareCardConfigList(BizShareCardConfig bizShareCardConfig)
    {
        return bizShareCardConfigMapper.selectBizShareCardConfigList(bizShareCardConfig);
    }

    /**
     * 新增分享卡片配置
     * 
     * @param bizShareCardConfig 分享卡片配置
     * @return 结果
     */
    @Override
    public int insertBizShareCardConfig(BizShareCardConfig bizShareCardConfig)
    {

        bizShareCardConfig.setDelFlag("0");
        bizShareCardConfig.setStatus("1");
        bizShareCardConfig.setCreateBy(String.valueOf(SecurityUtils.getUserId()));
        bizShareCardConfig.setCreateTime(DateUtils.getNowDate());
        return bizShareCardConfigMapper.insertBizShareCardConfig(bizShareCardConfig);
    }

    /**
     * 修改分享卡片配置
     * 
     * @param bizShareCardConfig 分享卡片配置
     * @return 结果
     */
    @Override
    public int updateBizShareCardConfig(BizShareCardConfig bizShareCardConfig)
    {
        bizShareCardConfig.setUpdateTime(DateUtils.getNowDate());
        bizShareCardConfig.setUpdateBy(String.valueOf(SecurityUtils.getUserId()));
        return bizShareCardConfigMapper.updateBizShareCardConfig(bizShareCardConfig);
    }

    /**
     * 批量删除分享卡片配置
     * 
     * @param ids 需要删除的分享卡片配置主键
     * @return 结果
     */
    @Override
    public int deleteBizShareCardConfigByIds(String[] ids)
    {
        return bizShareCardConfigMapper.deleteBizShareCardConfigByIds(ids);
    }

    /**
     * 删除分享卡片配置信息
     * 
     * @param id 分享卡片配置主键
     * @return 结果
     */
    @Override
    public int deleteBizShareCardConfigById(String id)
    {
        return bizShareCardConfigMapper.deleteBizShareCardConfigById(id);
    }
}
