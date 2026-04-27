package com.shop.biz.service.impl;

import java.util.List;
import com.shop.common.utils.DateUtils;
import com.shop.common.utils.SecurityUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import com.shop.biz.mapper.BizSlidesMapper;
import com.shop.biz.domain.BizSlides;
import com.shop.biz.service.IBizSlidesService;

/**
 * 轮播图Service业务层处理
 * 
 * @author shop
 * @date 2026-04-27
 */
@Service
public class BizSlidesServiceImpl implements IBizSlidesService 
{
    @Autowired
    private BizSlidesMapper bizSlidesMapper;

    /**
     * 查询轮播图
     * 
     * @param id 轮播图主键
     * @return 轮播图
     */
    @Override
    public BizSlides selectBizSlidesById(Long id)
    {
        return bizSlidesMapper.selectBizSlidesById(id);
    }

    /**
     * 查询轮播图列表
     * 
     * @param bizSlides 轮播图
     * @return 轮播图
     */
    @Override
    public List<BizSlides> selectBizSlidesList(BizSlides bizSlides)
    {
        return bizSlidesMapper.selectBizSlidesList(bizSlides);
    }

    /**
     * 新增轮播图
     * 
     * @param bizSlides 轮播图
     * @return 结果
     */
    @Override
    public int insertBizSlides(BizSlides bizSlides)
    {
        bizSlides.setCreateBy(String.valueOf(SecurityUtils.getUserId()));
        bizSlides.setCreateTime(DateUtils.getNowDate());
        bizSlides.setDelFlag( "1");
        return bizSlidesMapper.insertBizSlides(bizSlides);
    }

    /**
     * 修改轮播图
     * 
     * @param bizSlides 轮播图
     * @return 结果
     */
    @Override
    public int updateBizSlides(BizSlides bizSlides)
    {
        bizSlides.setUpdateTime(DateUtils.getNowDate());
        bizSlides.setUpdateBy(String.valueOf(SecurityUtils.getUserId()));
        return bizSlidesMapper.updateBizSlides(bizSlides);
    }

    /**
     * 批量删除轮播图
     * 
     * @param ids 需要删除的轮播图主键
     * @return 结果
     */
    @Override
    public int deleteBizSlidesByIds(Long[] ids)
    {
        return bizSlidesMapper.deleteBizSlidesByIds(ids);
    }

    /**
     * 删除轮播图信息
     * 
     * @param id 轮播图主键
     * @return 结果
     */
    @Override
    public int deleteBizSlidesById(Long id)
    {
        return bizSlidesMapper.deleteBizSlidesById(id);
    }
}
