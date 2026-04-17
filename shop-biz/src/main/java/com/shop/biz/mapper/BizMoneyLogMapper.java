package com.shop.biz.mapper;

import java.util.List;
import com.shop.biz.domain.BizMoneyLog;

/**
 * 资金变动日志Mapper接口
 * 
 * @author shop
 * @date 2026-04-17
 */
public interface BizMoneyLogMapper 
{
    /**
     * 查询资金变动日志
     * 
     * @param id 资金变动日志主键
     * @return 资金变动日志
     */
    public BizMoneyLog selectBizMoneyLogById(Long id);

    /**
     * 查询资金变动日志列表
     * 
     * @param bizMoneyLog 资金变动日志
     * @return 资金变动日志集合
     */
    public List<BizMoneyLog> selectBizMoneyLogList(BizMoneyLog bizMoneyLog);

    /**
     * 新增资金变动日志
     * 
     * @param bizMoneyLog 资金变动日志
     * @return 结果
     */
    public int insertBizMoneyLog(BizMoneyLog bizMoneyLog);

    /**
     * 修改资金变动日志
     * 
     * @param bizMoneyLog 资金变动日志
     * @return 结果
     */
    public int updateBizMoneyLog(BizMoneyLog bizMoneyLog);

    /**
     * 删除资金变动日志
     * 
     * @param id 资金变动日志主键
     * @return 结果
     */
    public int deleteBizMoneyLogById(Long id);

    /**
     * 批量删除资金变动日志
     * 
     * @param ids 需要删除的数据主键集合
     * @return 结果
     */
    public int deleteBizMoneyLogByIds(Long[] ids);
}
