package com.shop.biz.controller;

import java.util.List;

import com.shop.biz.domain.BizCategories;
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
import com.shop.biz.domain.BizProducts;
import com.shop.biz.service.IBizProductsService;
import com.shop.common.utils.poi.ExcelUtil;
import com.shop.common.core.page.TableDataInfo;

/**
 * 商品Controller
 * 
 * @author shop
 * @date 2026-04-11
 */
@RestController
@RequestMapping("/biz/products")
public class BizProductsController extends BaseController
{
    @Autowired
    private IBizProductsService bizProductsService;

    /**
     * 查询商品列表
     */
    @PreAuthorize("@ss.hasPermi('biz:products:list')")
    @GetMapping("/list")
    public TableDataInfo list(BizProducts bizProducts)
    {
        startPage();
        List<BizProducts> list = bizProductsService.selectBizProductsList(bizProducts);
        return getDataTable(list);
    }

    @GetMapping("/listAll")
    public AjaxResult  listAll (Long categoryId)
    {
        List<BizProducts> list = bizProductsService.selectBizProductsListByCategoriesId(categoryId);
        return AjaxResult.success(list);
    }

    /**
     * 导出商品列表
     */
    @PreAuthorize("@ss.hasPermi('biz:products:export')")
    @Log(title = "商品", businessType = BusinessType.EXPORT)
    @PostMapping("/export")
    public void export(HttpServletResponse response, BizProducts bizProducts)
    {
        List<BizProducts> list = bizProductsService.selectBizProductsList(bizProducts);
        ExcelUtil<BizProducts> util = new ExcelUtil<BizProducts>(BizProducts.class);
        util.exportExcel(response, list, "商品数据");
    }

    /**
     * 获取商品详细信息
     */
    @PreAuthorize("@ss.hasPermi('biz:products:query')")
    @GetMapping(value = "/{id}")
    public AjaxResult getInfo(@PathVariable("id") Long id)
    {
        return success(bizProductsService.selectBizProductsById(id));
    }

    /**
     * 新增商品
     */
    @Log(title = "商品", businessType = BusinessType.INSERT)
    @PostMapping
    public AjaxResult add(@RequestBody BizProducts bizProducts)
    {
        return toAjax(bizProductsService.insertBizProducts(bizProducts));
    }

    /**
     * 修改商品
     */
    @PreAuthorize("@ss.hasPermi('biz:products:edit')")
    @Log(title = "商品", businessType = BusinessType.UPDATE)
    @PutMapping
    public AjaxResult edit(@RequestBody BizProducts bizProducts)
    {
        return toAjax(bizProductsService.updateBizProducts(bizProducts));
    }

    /**
     * 删除商品
     */
    @PreAuthorize("@ss.hasPermi('biz:products:remove')")
    @Log(title = "商品", businessType = BusinessType.DELETE)
	@DeleteMapping("/{ids}")
    public AjaxResult remove(@PathVariable Long[] ids)
    {
        return toAjax(bizProductsService.deleteBizProductsByIds(ids));
    }


    //查询所有待审核商品
    @GetMapping("/listNoAudit")
    public TableDataInfo listNoAudit(){
        startPage();
        BizProducts bizProducts = new BizProducts();
        bizProducts.setAuditStatus("0");
        List<BizProducts> list = bizProductsService.selectBizProductsList(bizProducts);
        return getDataTable(list);
    }

    //查询我的待审核商品
    @GetMapping("/getMyProducts")
    public  AjaxResult getMyProducts(){
        List<BizProducts> list = bizProductsService.selectBizProductsListByUserId(getUserId());
        return AjaxResult.success(list);
    }

    //更改审核状态
    @PutMapping("/updateAuditStatus")
    public AjaxResult updateAuditStatus(@RequestBody BizProducts bizProducts){
        return toAjax(bizProductsService.updateAuditStatus(bizProducts));
    }

}
