package com.shop.biz.mapper;

import java.util.List;
import com.shop.biz.domain.BizShareCardConfig;

/**
 * 分享卡片配置Mapper接口
 * 
 * @author shop
 * @date 2026-04-12
 */
public interface BizShareCardConfigMapper 
{
    /**
     * 查询分享卡片配置
     * 
     * @param id 分享卡片配置主键
     * @return 分享卡片配置
     */
    public BizShareCardConfig selectBizShareCardConfigById(String id);

    public BizShareCardConfig selectBizShareCardConfigBytpl(String id);

    /**
     * 查询分享卡片配置列表
     * 
     * @param bizShareCardConfig 分享卡片配置
     * @return 分享卡片配置集合
     */
    public List<BizShareCardConfig> selectBizShareCardConfigList(BizShareCardConfig bizShareCardConfig);

    /**
     * 新增分享卡片配置
     * 
     * @param bizShareCardConfig 分享卡片配置
     * @return 结果
     */
    public int insertBizShareCardConfig(BizShareCardConfig bizShareCardConfig);

    /**
     * 修改分享卡片配置
     * 
     * @param bizShareCardConfig 分享卡片配置
     * @return 结果
     */
    public int updateBizShareCardConfig(BizShareCardConfig bizShareCardConfig);

    /**
     * 删除分享卡片配置
     * 
     * @param id 分享卡片配置主键
     * @return 结果
     */
    public int deleteBizShareCardConfigById(String id);

    /**
     * 批量删除分享卡片配置
     * 
     * @param ids 需要删除的数据主键集合
     * @return 结果
     */
    public int deleteBizShareCardConfigByIds(String[] ids);
}
