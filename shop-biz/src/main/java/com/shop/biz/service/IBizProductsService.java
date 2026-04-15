package com.shop.biz.service;

import java.util.List;
import com.shop.biz.domain.BizProducts;

/**
 * 商品Service接口
 * 
 * @author shop
 * @date 2026-04-11
 */
public interface IBizProductsService 
{
    /**
     * 查询商品
     * 
     * @param id 商品主键
     * @return 商品
     */
    public BizProducts selectBizProductsById(Long id);

    /**
     * 查询商品列表
     * 
     * @param bizProducts 商品
     * @return 商品集合
     */
    public List<BizProducts> selectBizProductsList(BizProducts bizProducts);

    public List<BizProducts> selectBizProductsListByCategoriesId(Long categoryId);

    /**
     * 新增商品
     * 
     * @param bizProducts 商品
     * @return 结果
     */
    public int insertBizProducts(BizProducts bizProducts);

    /**
     * 修改商品
     * 
     * @param bizProducts 商品
     * @return 结果
     */
    public int updateBizProducts(BizProducts bizProducts);
    public int updateAuditStatus(BizProducts bizProducts);

    /**
     * 批量删除商品
     * 
     * @param ids 需要删除的商品主键集合
     * @return 结果
     */
    public int deleteBizProductsByIds(Long[] ids);

    /**
     * 删除商品信息
     * 
     * @param id 商品主键
     * @return 结果
     */
    public int deleteBizProductsById(Long id);

    List<BizProducts> selectBizProductsListByUserId(Long userId);
}
