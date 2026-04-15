package com.shop.biz.mapper;

import java.util.List;

import com.shop.biz.domain.BizProductSimple;
import com.shop.biz.domain.BizProducts;
import org.apache.ibatis.annotations.Param;

/**
 * 商品Mapper接口
 * 
 * @author shop
 * @date 2026-04-11
 */
public interface BizProductsMapper 
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
    BizProductSimple selectSimpleProductById(@Param("id") Integer id);

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

    /**
     * 删除商品
     * 
     * @param id 商品主键
     * @return 结果
     */
    public int deleteBizProductsById(Long id);

    /**
     * 批量删除商品
     * 
     * @param ids 需要删除的数据主键集合
     * @return 结果
     */
    public int deleteBizProductsByIds(Long[] ids);

    List<BizProducts> selectBizProductsListByCategoriesId(Long categoryId);

    List<BizProducts> selectBizProductsListByUserId(Long userId);
}
