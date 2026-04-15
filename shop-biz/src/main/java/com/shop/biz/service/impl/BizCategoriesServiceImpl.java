package com.shop.biz.service.impl;

import java.util.List;
import com.shop.common.utils.DateUtils;
import com.shop.common.utils.SecurityUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import com.shop.biz.mapper.BizCategoriesMapper;
import com.shop.biz.domain.BizCategories;
import com.shop.biz.service.IBizCategoriesService;

/**
 * 商品分类Service业务层处理
 * 
 * @author shop
 * @date 2026-04-10
 */
@Service
public class BizCategoriesServiceImpl implements IBizCategoriesService 
{
    @Autowired
    private BizCategoriesMapper bizCategoriesMapper;

    /**
     * 查询商品分类
     * 
     * @param id 商品分类主键
     * @return 商品分类
     */
    @Override
    public BizCategories selectBizCategoriesById(Long id)
    {
        return bizCategoriesMapper.selectBizCategoriesById(id);
    }

    /**
     * 查询商品分类列表
     * 
     * @param bizCategories 商品分类
     * @return 商品分类
     */
    @Override
    public List<BizCategories> selectBizCategoriesList(BizCategories bizCategories)
    {
        return bizCategoriesMapper.selectBizCategoriesList(bizCategories);
    }

    /**
     * 新增商品分类
     * 
     * @param bizCategories 商品分类
     * @return 结果
     */
    @Override
    public int insertBizCategories(BizCategories bizCategories)
    {
        bizCategories.setCreateBy(String.valueOf(SecurityUtils.getUserId()));
        bizCategories.setCreateTime(DateUtils.getNowDate());
        bizCategories.setDelFalg("1");
        return bizCategoriesMapper.insertBizCategories(bizCategories);
    }

    /**
     * 修改商品分类
     * 
     * @param bizCategories 商品分类
     * @return 结果
     */
    @Override
    public int updateBizCategories(BizCategories bizCategories)
    {
        bizCategories.setUpdateTime(DateUtils.getNowDate());
        bizCategories.setUpdateBy(String.valueOf(SecurityUtils.getUserId()));
        return bizCategoriesMapper.updateBizCategories(bizCategories);
    }

    /**
     * 批量删除商品分类
     * 
     * @param ids 需要删除的商品分类主键
     * @return 结果
     */
    @Override
    public int deleteBizCategoriesByIds(Long[] ids)
    {
        return bizCategoriesMapper.deleteBizCategoriesByIds(ids);
    }

    /**
     * 删除商品分类信息
     * 
     * @param id 商品分类主键
     * @return 结果
     */
    @Override
    public int deleteBizCategoriesById(Long id)
    {
        return bizCategoriesMapper.deleteBizCategoriesById(id);
    }
}
