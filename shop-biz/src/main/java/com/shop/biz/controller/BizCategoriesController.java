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
import com.shop.biz.domain.BizCategories;
import com.shop.biz.service.IBizCategoriesService;
import com.shop.common.utils.poi.ExcelUtil;
import com.shop.common.core.page.TableDataInfo;

/**
 * 商品分类Controller
 * 
 * @author shop
 * @date 2026-04-10
 */
@RestController
@RequestMapping("/biz/categories")
public class BizCategoriesController extends BaseController
{
    @Autowired
    private IBizCategoriesService bizCategoriesService;



    /**
     * 查询商品分类列表
     */
    @PreAuthorize("@ss.hasPermi('biz:categories:list')")
    @GetMapping("/list")
    public TableDataInfo list(BizCategories bizCategories)
    {
        startPage();
        List<BizCategories> list = bizCategoriesService.selectBizCategoriesList(bizCategories);
        return getDataTable(list);
    }

    /**
     * 查询商品分类
     */
    @GetMapping("/listAll")
    public AjaxResult  listAll(BizCategories bizCategories)
    {
        List<BizCategories> list = bizCategoriesService.selectBizCategoriesList(bizCategories);
        return AjaxResult.success(list);
    }

    /**
     * 导出商品分类列表
     */
    @PreAuthorize("@ss.hasPermi('biz:categories:export')")
    @Log(title = "商品分类", businessType = BusinessType.EXPORT)
    @PostMapping("/export")
    public void export(HttpServletResponse response, BizCategories bizCategories)
    {
        List<BizCategories> list = bizCategoriesService.selectBizCategoriesList(bizCategories);
        ExcelUtil<BizCategories> util = new ExcelUtil<BizCategories>(BizCategories.class);
        util.exportExcel(response, list, "商品分类数据");
    }

    /**
     * 获取商品分类详细信息
     */
    @PreAuthorize("@ss.hasPermi('biz:categories:query')")
    @GetMapping(value = "/{id}")
    public AjaxResult getInfo(@PathVariable("id") Long id)
    {
        return success(bizCategoriesService.selectBizCategoriesById(id));
    }

    /**
     * 新增商品分类
     */
    @PreAuthorize("@ss.hasPermi('biz:categories:add')")
    @Log(title = "商品分类", businessType = BusinessType.INSERT)
    @PostMapping
    public AjaxResult add(@RequestBody BizCategories bizCategories)
    {
        return toAjax(bizCategoriesService.insertBizCategories(bizCategories));
    }

    /**
     * 修改商品分类
     */
    @PreAuthorize("@ss.hasPermi('biz:categories:edit')")
    @Log(title = "商品分类", businessType = BusinessType.UPDATE)
    @PutMapping
    public AjaxResult edit(@RequestBody BizCategories bizCategories)
    {
        return toAjax(bizCategoriesService.updateBizCategories(bizCategories));
    }

    /**
     * 删除商品分类
     */
    @PreAuthorize("@ss.hasPermi('biz:categories:remove')")
    @Log(title = "商品分类", businessType = BusinessType.DELETE)
	@DeleteMapping("/{ids}")
    public AjaxResult remove(@PathVariable Long[] ids)
    {
        return toAjax(bizCategoriesService.deleteBizCategoriesByIds(ids));
    }
}
