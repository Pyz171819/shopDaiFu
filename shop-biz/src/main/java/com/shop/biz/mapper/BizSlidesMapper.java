package com.shop.biz.mapper;

import java.util.List;
import com.shop.biz.domain.BizSlides;

/**
 * 轮播图Mapper接口
 * 
 * @author shop
 * @date 2026-04-27
 */
public interface BizSlidesMapper 
{
    /**
     * 查询轮播图
     * 
     * @param id 轮播图主键
     * @return 轮播图
     */
    public BizSlides selectBizSlidesById(Long id);

    /**
     * 查询轮播图列表
     * 
     * @param bizSlides 轮播图
     * @return 轮播图集合
     */
    public List<BizSlides> selectBizSlidesList(BizSlides bizSlides);

    /**
     * 新增轮播图
     * 
     * @param bizSlides 轮播图
     * @return 结果
     */
    public int insertBizSlides(BizSlides bizSlides);

    /**
     * 修改轮播图
     * 
     * @param bizSlides 轮播图
     * @return 结果
     */
    public int updateBizSlides(BizSlides bizSlides);

    /**
     * 删除轮播图
     * 
     * @param id 轮播图主键
     * @return 结果
     */
    public int deleteBizSlidesById(Long id);

    /**
     * 批量删除轮播图
     * 
     * @param ids 需要删除的数据主键集合
     * @return 结果
     */
    public int deleteBizSlidesByIds(Long[] ids);
}
