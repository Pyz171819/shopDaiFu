package com.shop.biz.service.impl;

import java.util.List;

import com.shop.common.core.domain.entity.SysUser;
import com.shop.common.utils.DateUtils;
import com.shop.common.utils.SecurityUtils;
import com.shop.system.mapper.SysUserMapper;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.security.core.parameters.P;
import org.springframework.stereotype.Service;
import com.shop.biz.mapper.BizProductsMapper;
import com.shop.biz.domain.BizProducts;
import com.shop.biz.service.IBizProductsService;

/**
 * 商品Service业务层处理
 * 
 * @author shop
 * @date 2026-04-11
 */
@Service
public class BizProductsServiceImpl implements IBizProductsService 
{
    @Autowired
    private BizProductsMapper bizProductsMapper;

    @Autowired
    private SysUserMapper sysUserMapper;

    /**
     * 查询商品
     * 
     * @param id 商品主键
     * @return 商品
     */
    @Override
    public BizProducts selectBizProductsById(Long id)
    {
        return bizProductsMapper.selectBizProductsById(id);
    }

    /**
     * 查询商品列表
     * 
     * @param bizProducts 商品
     * @return 商品
     */
    @Override
    public List<BizProducts> selectBizProductsList(BizProducts bizProducts)
    {
        return bizProductsMapper.selectBizProductsList(bizProducts);
    }

    @Override
    public List<BizProducts> selectBizProductsListByCategoriesId(Long categoryId) {
        return bizProductsMapper.selectBizProductsListByCategoriesId(categoryId);
    }

    /**
     * 新增商品
     * 
     * @param bizProducts 商品
     * @return 结果
     */
    @Override
    public int insertBizProducts(BizProducts bizProducts)
    {
        SysUser sysUser = sysUserMapper.selectUserById(SecurityUtils.getUserId());



        if (sysUser != null){
            if (sysUser.getBizUser().getPermAddProduct().equals("0")){
                return -1;
            }
            if (sysUser.getUserType().equals("00")) {
                bizProducts.setAuditStatus("1");
            }
            if (sysUser.getUserType().equals("01")) {
                bizProducts.setAuditStatus("0");
            }
        }

        bizProducts.setUserId(sysUser.getUserId());
        bizProducts.setDelFlag("1");
        bizProducts.setStatus("1");
        bizProducts.setCreateBy(String.valueOf(SecurityUtils.getUserId()));
        bizProducts.setCreateTime(DateUtils.getNowDate());
        return bizProductsMapper.insertBizProducts(bizProducts);
    }

    /**
     * 修改商品
     * 
     * @param bizProducts 商品
     * @return 结果
     */
    @Override
    public int updateBizProducts(BizProducts bizProducts)
    {
        bizProducts.setUpdateBy(String.valueOf(SecurityUtils.getUserId()));
        bizProducts.setUpdateTime(DateUtils.getNowDate());
        return bizProductsMapper.updateBizProducts(bizProducts);
    }

    @Override
    public int updateAuditStatus(BizProducts bizProducts)
    {
        bizProducts.setUpdateBy(String.valueOf(SecurityUtils.getUserId()));
        bizProducts.setUpdateTime(DateUtils.getNowDate());
        return bizProductsMapper.updateBizProducts(bizProducts);
    }

    /**
     * 批量删除商品
     * 
     * @param ids 需要删除的商品主键
     * @return 结果
     */
    @Override
    public int deleteBizProductsByIds(Long[] ids)
    {
        return bizProductsMapper.deleteBizProductsByIds(ids);
    }

    /**
     * 删除商品信息
     * 
     * @param id 商品主键
     * @return 结果
     */
    @Override
    public int deleteBizProductsById(Long id)
    {
        return bizProductsMapper.deleteBizProductsById(id);
    }

    @Override
    public List<BizProducts> selectBizProductsListByUserId(Long userId) {
        return bizProductsMapper.selectBizProductsListByUserId(userId);

    }
}
