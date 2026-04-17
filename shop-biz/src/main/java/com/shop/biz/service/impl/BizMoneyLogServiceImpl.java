package com.shop.biz.service.impl;

import java.util.List;
import com.shop.common.utils.DateUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import com.shop.biz.mapper.BizMoneyLogMapper;
import com.shop.biz.domain.BizMoneyLog;
import com.shop.biz.service.IBizMoneyLogService;

/**
 * 资金变动日志Service业务层处理
 * 
 * @author shop
 * @date 2026-04-17
 */
@Service
public class BizMoneyLogServiceImpl implements IBizMoneyLogService 
{
    @Autowired
    private BizMoneyLogMapper bizMoneyLogMapper;

    /**
     * 查询资金变动日志
     * 
     * @param id 资金变动日志主键
     * @return 资金变动日志
     */
    @Override
    public BizMoneyLog selectBizMoneyLogById(Long id)
    {
        return bizMoneyLogMapper.selectBizMoneyLogById(id);
    }

    /**
     * 查询资金变动日志列表
     * 
     * @param bizMoneyLog 资金变动日志
     * @return 资金变动日志
     */
    @Override
    public List<BizMoneyLog> selectBizMoneyLogList(BizMoneyLog bizMoneyLog)
    {
        return bizMoneyLogMapper.selectBizMoneyLogList(bizMoneyLog);
    }

    /**
     * 新增资金变动日志
     * 
     * @param bizMoneyLog 资金变动日志
     * @return 结果
     */
    @Override
    public int insertBizMoneyLog(BizMoneyLog bizMoneyLog)
    {
        bizMoneyLog.setCreateTime(DateUtils.getNowDate());
        return bizMoneyLogMapper.insertBizMoneyLog(bizMoneyLog);
    }

    /**
     * 修改资金变动日志
     * 
     * @param bizMoneyLog 资金变动日志
     * @return 结果
     */
    @Override
    public int updateBizMoneyLog(BizMoneyLog bizMoneyLog)
    {
        bizMoneyLog.setUpdateTime(DateUtils.getNowDate());
        return bizMoneyLogMapper.updateBizMoneyLog(bizMoneyLog);
    }

    /**
     * 批量删除资金变动日志
     * 
     * @param ids 需要删除的资金变动日志主键
     * @return 结果
     */
    @Override
    public int deleteBizMoneyLogByIds(Long[] ids)
    {
        return bizMoneyLogMapper.deleteBizMoneyLogByIds(ids);
    }

    /**
     * 删除资金变动日志信息
     * 
     * @param id 资金变动日志主键
     * @return 结果
     */
    @Override
    public int deleteBizMoneyLogById(Long id)
    {
        return bizMoneyLogMapper.deleteBizMoneyLogById(id);
    }
}
