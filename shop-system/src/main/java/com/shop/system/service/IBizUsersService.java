package com.shop.system.service;

import java.util.List;
import com.shop.common.core.domain.entity.BizUsers;


/**
 * 前台用户业务扩展Service接口
 * 
 * @author shop
 * @date 2026-04-11
 */
public interface IBizUsersService 
{
    /**
     * 查询前台用户业务扩展
     * 
     * @param id 前台用户业务扩展主键
     * @return 前台用户业务扩展
     */
    public BizUsers selectBizUsersById(Long id);

    /**
     * 查询前台用户业务扩展列表
     * 
     * @param bizUsers 前台用户业务扩展
     * @return 前台用户业务扩展集合
     */
    public List<BizUsers> selectBizUsersList(BizUsers bizUsers);

    /**
     * 新增前台用户业务扩展
     * 
     * @param bizUsers 前台用户业务扩展
     * @return 结果
     */
    public int insertBizUsers(BizUsers bizUsers);

    /**
     * 修改前台用户业务扩展
     * 
     * @param bizUsers 前台用户业务扩展
     * @return 结果
     */
    public int updateBizUsers(BizUsers bizUsers);
    public int updateBizUsersByUserId(BizUsers bizUsers);

    /**
     * 批量删除前台用户业务扩展
     * 
     * @param ids 需要删除的前台用户业务扩展主键集合
     * @return 结果
     */
    public int deleteBizUsersByIds(Long[] ids);

    /**
     * 删除前台用户业务扩展信息
     * 
     * @param id 前台用户业务扩展主键
     * @return 结果
     */
    public int deleteBizUsersById(Long id);

    int deleteBizUsersBySysUserId(Long userId);
}
