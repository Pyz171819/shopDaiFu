package com.shop.system.service.impl;

import java.util.List;

import com.shop.common.core.domain.entity.BizUsers;
import com.shop.common.utils.DateUtils;
import com.shop.common.utils.SecurityUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import com.shop.system.mapper.BizUsersMapper;

import com.shop.system.service.IBizUsersService;

/**
 * 前台用户业务扩展Service业务层处理
 * 
 * @author shop
 * @date 2026-04-11
 */
@Service
public class BizUsersServiceImpl implements IBizUsersService 
{
    @Autowired
    private BizUsersMapper bizUsersMapper;

    /**
     * 查询前台用户业务扩展
     * 
     * @param id 前台用户业务扩展主键
     * @return 前台用户业务扩展
     */
    @Override
    public BizUsers selectBizUsersById(Long id)
    {
        return bizUsersMapper.selectBizUsersById(id);
    }

    /**
     * 查询前台用户业务扩展列表
     * 
     * @param bizUsers 前台用户业务扩展
     * @return 前台用户业务扩展
     */
    @Override
    public List<BizUsers> selectBizUsersList(BizUsers bizUsers)
    {
        return bizUsersMapper.selectBizUsersList(bizUsers);
    }

    /**
     * 新增前台用户业务扩展
     * 
     * @param bizUsers 前台用户业务扩展
     * @return 结果
     */
    @Override
    public int insertBizUsers(BizUsers bizUsers)
    {
        bizUsers.setCreateBy(String.valueOf(SecurityUtils.getUserId()));
        bizUsers.setCreateTime(DateUtils.getNowDate());
        bizUsers.setDelFlag("0");
        return bizUsersMapper.insertBizUsers(bizUsers);
    }

    /**
     * 修改前台用户业务扩展
     * 
     * @param bizUsers 前台用户业务扩展
     * @return 结果
     */
    @Override
    public int updateBizUsers(BizUsers bizUsers)
    {
        bizUsers.setUpdateBy(String.valueOf(SecurityUtils.getUserId()));
        bizUsers.setUpdateTime(DateUtils.getNowDate());
        return bizUsersMapper.updateBizUsers(bizUsers);
    }

    @Override
    public int updateBizUsersByUserId(BizUsers bizUsers)
    {
        bizUsers.setUpdateBy(String.valueOf(SecurityUtils.getUserId()));
        bizUsers.setUpdateTime(DateUtils.getNowDate());
        return bizUsersMapper.updateBizUsersByUserId(bizUsers);
    }

    @Override
    public int updateBizUsersByUserIdAndNotice(BizUsers bizUsers)
    {
        bizUsers.setUpdateBy("支付成功回调更新");
        bizUsers.setUpdateTime(DateUtils.getNowDate());
        return bizUsersMapper.updateBizUsersByUserId(bizUsers);
    }

    /**
     * 批量删除前台用户业务扩展
     * 
     * @param ids 需要删除的前台用户业务扩展主键
     * @return 结果
     */
    @Override
    public int deleteBizUsersByIds(Long[] ids)
    {
        return bizUsersMapper.deleteBizUsersByIds(ids);
    }

    /**
     * 删除前台用户业务扩展信息
     * 
     * @param id 前台用户业务扩展主键
     * @return 结果
     */
    @Override
    public int deleteBizUsersById(Long id)
    {
        return bizUsersMapper.deleteBizUsersById(id);
    }

    @Override
    public int deleteBizUsersBySysUserId(Long userId) {
        return bizUsersMapper.deleteBizUsersBySysUserId(userId);
    }
}
