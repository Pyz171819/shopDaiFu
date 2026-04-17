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
import com.shop.biz.domain.BizMoneyLog;
import com.shop.biz.service.IBizMoneyLogService;
import com.shop.common.utils.poi.ExcelUtil;
import com.shop.common.core.page.TableDataInfo;

/**
 * 资金变动日志Controller
 * 
 * @author shop
 * @date 2026-04-17
 */
@RestController
@RequestMapping("/biz/bizMoneyLog")
public class BizMoneyLogController extends BaseController
{
    @Autowired
    private IBizMoneyLogService bizMoneyLogService;

    /**
     * 查询资金变动日志列表
     */
    @PreAuthorize("@ss.hasPermi('biz:bizMoneyLog:list')")
    @GetMapping("/list")
    public TableDataInfo list(BizMoneyLog bizMoneyLog)
    {
        startPage();
        List<BizMoneyLog> list = bizMoneyLogService.selectBizMoneyLogList(bizMoneyLog);
        return getDataTable(list);
    }

    /**
     * 导出资金变动日志列表
     */
    @PreAuthorize("@ss.hasPermi('biz:bizMoneyLog:export')")
    @Log(title = "资金变动日志", businessType = BusinessType.EXPORT)
    @PostMapping("/export")
    public void export(HttpServletResponse response, BizMoneyLog bizMoneyLog)
    {
        List<BizMoneyLog> list = bizMoneyLogService.selectBizMoneyLogList(bizMoneyLog);
        ExcelUtil<BizMoneyLog> util = new ExcelUtil<BizMoneyLog>(BizMoneyLog.class);
        util.exportExcel(response, list, "资金变动日志数据");
    }

    /**
     * 获取资金变动日志详细信息
     */
    @PreAuthorize("@ss.hasPermi('biz:bizMoneyLog:query')")
    @GetMapping(value = "/{id}")
    public AjaxResult getInfo(@PathVariable("id") Long id)
    {
        return success(bizMoneyLogService.selectBizMoneyLogById(id));
    }

    /**
     * 新增资金变动日志
     */
    @PreAuthorize("@ss.hasPermi('biz:bizMoneyLog:add')")
    @Log(title = "资金变动日志", businessType = BusinessType.INSERT)
    @PostMapping
    public AjaxResult add(@RequestBody BizMoneyLog bizMoneyLog)
    {
        return toAjax(bizMoneyLogService.insertBizMoneyLog(bizMoneyLog));
    }

    /**
     * 修改资金变动日志
     */
    @PreAuthorize("@ss.hasPermi('biz:bizMoneyLog:edit')")
    @Log(title = "资金变动日志", businessType = BusinessType.UPDATE)
    @PutMapping
    public AjaxResult edit(@RequestBody BizMoneyLog bizMoneyLog)
    {
        return toAjax(bizMoneyLogService.updateBizMoneyLog(bizMoneyLog));
    }

    /**
     * 删除资金变动日志
     */
    @PreAuthorize("@ss.hasPermi('biz:bizMoneyLog:remove')")
    @Log(title = "资金变动日志", businessType = BusinessType.DELETE)
	@DeleteMapping("/{ids}")
    public AjaxResult remove(@PathVariable Long[] ids)
    {
        return toAjax(bizMoneyLogService.deleteBizMoneyLogByIds(ids));
    }
}
