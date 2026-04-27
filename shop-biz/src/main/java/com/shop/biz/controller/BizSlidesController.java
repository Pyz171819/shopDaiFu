package com.shop.biz.controller;

import java.util.List;
import jakarta.servlet.http.HttpServletResponse;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.PutMapping;
import org.springframework.web.bind.annotation.DeleteMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;
import com.shop.common.annotation.Log;
import com.shop.common.core.controller.BaseController;
import com.shop.common.core.domain.AjaxResult;
import com.shop.common.enums.BusinessType;
import com.shop.biz.domain.BizSlides;
import com.shop.biz.service.IBizSlidesService;
import com.shop.common.utils.poi.ExcelUtil;
import com.shop.common.core.page.TableDataInfo;

/**
 * 轮播图Controller
 * 
 * @author shop
 * @date 2026-04-27
 */
@RestController
@RequestMapping("/biz/slides")
public class BizSlidesController extends BaseController
{
    @Autowired
    private IBizSlidesService bizSlidesService;

    /**
     * 查询轮播图列表
     */
    @PreAuthorize("@ss.hasPermi('biz:bizSlides:list')")
    @GetMapping("/list")
    public TableDataInfo list(BizSlides bizSlides)
    {
        startPage();
        List<BizSlides> list = bizSlidesService.selectBizSlidesList(bizSlides);
        return getDataTable(list);
    }

    @GetMapping("/listAll")
    public AjaxResult listAll(BizSlides bizSlides)
    {
        return AjaxResult.success(bizSlidesService.selectBizSlidesList(bizSlides));
    }

    /**
     * 导出轮播图列表
     */
    @PreAuthorize("@ss.hasPermi('biz:bizSlides:export')")
    @Log(title = "轮播图", businessType = BusinessType.EXPORT)
    @PostMapping("/export")
    public void export(HttpServletResponse response, BizSlides bizSlides)
    {
        List<BizSlides> list = bizSlidesService.selectBizSlidesList(bizSlides);
        ExcelUtil<BizSlides> util = new ExcelUtil<BizSlides>(BizSlides.class);
        util.exportExcel(response, list, "轮播图数据");
    }

    /**
     * 获取轮播图详细信息
     */
    @PreAuthorize("@ss.hasPermi('biz:bizSlides:query')")
    @GetMapping(value = "/{id}")
    public AjaxResult getInfo(@PathVariable("id") Long id)
    {
        return success(bizSlidesService.selectBizSlidesById(id));
    }

    /**
     * 新增轮播图
     */
    @PreAuthorize("@ss.hasPermi('biz:bizSlides:add')")
    @Log(title = "轮播图", businessType = BusinessType.INSERT)
    @PostMapping
    public AjaxResult add(@RequestBody BizSlides bizSlides)
    {
        return toAjax(bizSlidesService.insertBizSlides(bizSlides));
    }

    /**
     * 修改轮播图
     */
    @PreAuthorize("@ss.hasPermi('biz:bizSlides:edit')")
    @Log(title = "轮播图", businessType = BusinessType.UPDATE)
    @PutMapping
    public AjaxResult edit(@RequestBody BizSlides bizSlides)
    {
        return toAjax(bizSlidesService.updateBizSlides(bizSlides));
    }

    /**
     * 删除轮播图
     */
    @PreAuthorize("@ss.hasPermi('biz:bizSlides:remove')")
    @Log(title = "轮播图", businessType = BusinessType.DELETE)
	@DeleteMapping("/{ids}")
    public AjaxResult remove(@PathVariable Long[] ids)
    {
        return toAjax(bizSlidesService.deleteBizSlidesByIds(ids));
    }
}
