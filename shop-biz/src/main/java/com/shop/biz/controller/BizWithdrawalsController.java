package com.shop.biz.controller;

import java.util.List;
import jakarta.servlet.http.HttpServletResponse;
import jakarta.validation.Valid;
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
import com.shop.biz.domain.BizWithdrawals;
import com.shop.biz.service.IBizWithdrawalsService;
import com.shop.common.utils.poi.ExcelUtil;
import com.shop.common.core.page.TableDataInfo;

/**
 * 提现申请Controller
 * 
 * @author shop
 * @date 2026-04-17
 */
@RestController
@RequestMapping("/biz/bizWithdrawals")
public class BizWithdrawalsController extends BaseController
{
    @Autowired
    private IBizWithdrawalsService bizWithdrawalsService;

    /**
     * 查询提现申请列表
     */
    @PreAuthorize("@ss.hasPermi('biz:bizWithdrawals:list')")
    @GetMapping("/list")
    public TableDataInfo list(BizWithdrawals bizWithdrawals)
    {
        startPage();
        List<BizWithdrawals> list = bizWithdrawalsService.selectBizWithdrawalsList(bizWithdrawals);
        return getDataTable(list);
    }

    @GetMapping("/listAllByUserId")
    public AjaxResult listAllByUserId(BizWithdrawals bizWithdrawals)
    {
        return  AjaxResult.success(bizWithdrawalsService.listAllByUserId(bizWithdrawals));
    }

    /**
     * 导出提现申请列表
     */
    @PreAuthorize("@ss.hasPermi('biz:bizWithdrawals:export')")
    @Log(title = "提现申请", businessType = BusinessType.EXPORT)
    @PostMapping("/export")
    public void export(HttpServletResponse response, BizWithdrawals bizWithdrawals)
    {
        List<BizWithdrawals> list = bizWithdrawalsService.selectBizWithdrawalsList(bizWithdrawals);
        ExcelUtil<BizWithdrawals> util = new ExcelUtil<BizWithdrawals>(BizWithdrawals.class);
        util.exportExcel(response, list, "提现申请数据");
    }

    /**
     * 获取提现申请详细信息
     */
    @PreAuthorize("@ss.hasPermi('biz:bizWithdrawals:query')")
    @GetMapping(value = "/{id}")
    public AjaxResult getInfo(@PathVariable("id") Long id)
    {
        return success(bizWithdrawalsService.selectBizWithdrawalsById(id));
    }

    /**
     * 新增提现申请
     */
//    @PreAuthorize("@ss.hasPermi('biz:bizWithdrawals:add')")
    @Log(title = "提现申请", businessType = BusinessType.INSERT)
    @PostMapping
    public AjaxResult add(@RequestBody BizWithdrawals bizWithdrawals)
    {
        return toAjax(bizWithdrawalsService.insertBizWithdrawals(bizWithdrawals));
    }

    /**
     * 修改提现申请
     */
    @PreAuthorize("@ss.hasPermi('biz:bizWithdrawals:edit')")
    @Log(title = "提现申请", businessType = BusinessType.UPDATE)
    @PutMapping
    public AjaxResult edit(@RequestBody BizWithdrawals bizWithdrawals)
    {
        return toAjax(bizWithdrawalsService.updateBizWithdrawals(bizWithdrawals));
    }

    /**
     * 删除提现申请
     */
    @PreAuthorize("@ss.hasPermi('biz:bizWithdrawals:remove')")
    @Log(title = "提现申请", businessType = BusinessType.DELETE)
	@DeleteMapping("/{ids}")
    public AjaxResult remove(@PathVariable Long[] ids)
    {
        return toAjax(bizWithdrawalsService.deleteBizWithdrawalsByIds(ids));
    }

    /**
     * 提现审核
     */
    @PreAuthorize("@ss.hasPermi('biz:bizWithdrawals:audit')")
    @Log(title = "提现申请审核", businessType = BusinessType.UPDATE)
    @PutMapping("/audit")
    public AjaxResult audit(@RequestBody BizWithdrawals bo) {
        System.out.println(" tixian");
        return toAjax(bizWithdrawalsService.auditBizWithdrawals(bo));
    }
}
