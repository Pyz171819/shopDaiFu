package com.shop.biz.service;

import java.util.List;
import com.shop.biz.domain.BizWithdrawals;

/**
 * 提现申请Service接口
 * 
 * @author shop
 * @date 2026-04-17
 */
public interface IBizWithdrawalsService 
{
    /**
     * 查询提现申请
     * 
     * @param id 提现申请主键
     * @return 提现申请
     */
    public BizWithdrawals selectBizWithdrawalsById(Long id);

    /**
     * 查询提现申请列表
     * 
     * @param bizWithdrawals 提现申请
     * @return 提现申请集合
     */
    public List<BizWithdrawals> selectBizWithdrawalsList(BizWithdrawals bizWithdrawals);

    public List<BizWithdrawals> listAllByUserId(BizWithdrawals bizWithdrawals);


    /**
     * 新增提现申请
     * 
     * @param bizWithdrawals 提现申请
     * @return 结果
     */
    public int insertBizWithdrawals(BizWithdrawals bizWithdrawals);

    /**
     * 修改提现申请
     * 
     * @param bizWithdrawals 提现申请
     * @return 结果
     */
    public int updateBizWithdrawals(BizWithdrawals bizWithdrawals);

    /**
     * 批量删除提现申请
     * 
     * @param ids 需要删除的提现申请主键集合
     * @return 结果
     */
    public int deleteBizWithdrawalsByIds(Long[] ids);

    /**
     * 删除提现申请信息
     * 
     * @param id 提现申请主键
     * @return 结果
     */
    public int deleteBizWithdrawalsById(Long id);

    /**
     * 提现审核
     */
    int auditBizWithdrawals(BizWithdrawals bo);
}
