package com.shop.biz.service;

import java.util.List;
import com.shop.biz.domain.BizCategories;

/**
 * 商品分类Service接口
 * 
 * @author shop
 * @date 2026-04-10
 */
public interface IBizCategoriesService 
{
    /**
     * 查询商品分类
     * 
     * @param id 商品分类主键
     * @return 商品分类
     */
    public BizCategories selectBizCategoriesById(Long id);

    /**
     * 查询商品分类列表
     * 
     * @param bizCategories 商品分类
     * @return 商品分类集合
     */
    public List<BizCategories> selectBizCategoriesList(BizCategories bizCategories);

    /**
     * 新增商品分类
     * 
     * @param bizCategories 商品分类
     * @return 结果
     */
    public int insertBizCategories(BizCategories bizCategories);

    /**
     * 修改商品分类
     * 
     * @param bizCategories 商品分类
     * @return 结果
     */
    public int updateBizCategories(BizCategories bizCategories);

    /**
     * 批量删除商品分类
     * 
     * @param ids 需要删除的商品分类主键集合
     * @return 结果
     */
    public int deleteBizCategoriesByIds(Long[] ids);

    /**
     * 删除商品分类信息
     * 
     * @param id 商品分类主键
     * @return 结果
     */
    public int deleteBizCategoriesById(Long id);
}
