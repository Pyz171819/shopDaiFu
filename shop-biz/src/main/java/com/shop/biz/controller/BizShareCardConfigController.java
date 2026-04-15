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
import com.shop.biz.domain.BizShareCardConfig;
import com.shop.biz.service.IBizShareCardConfigService;
import com.shop.common.utils.poi.ExcelUtil;
import com.shop.common.core.page.TableDataInfo;

/**
 * 分享卡片配置Controller
 * 
 * @author shop
 * @date 2026-04-12
 */
@RestController
@RequestMapping("/biz/bizShareCardConfig")
public class BizShareCardConfigController extends BaseController
{
    @Autowired
    private IBizShareCardConfigService bizShareCardConfigService;

    /**
     * 查询分享卡片配置列表
     */
    @PreAuthorize("@ss.hasPermi('biz:bizShareCardConfig:list')")
    @GetMapping("/list")
    public TableDataInfo list(BizShareCardConfig bizShareCardConfig)
    {
        startPage();
        List<BizShareCardConfig> list = bizShareCardConfigService.selectBizShareCardConfigList(bizShareCardConfig);
        return getDataTable(list);
    }
    @GetMapping("/listAll")
    public AjaxResult listAll(BizShareCardConfig bizShareCardConfig)
    {
        List<BizShareCardConfig> list = bizShareCardConfigService.selectBizShareCardConfigList(bizShareCardConfig);
        return AjaxResult.success(list);
    }

    /**
     * 导出分享卡片配置列表
     */
    @PreAuthorize("@ss.hasPermi('biz:bizShareCardConfig:export')")
    @Log(title = "分享卡片配置", businessType = BusinessType.EXPORT)
    @PostMapping("/export")
    public void export(HttpServletResponse response, BizShareCardConfig bizShareCardConfig)
    {
        List<BizShareCardConfig> list = bizShareCardConfigService.selectBizShareCardConfigList(bizShareCardConfig);
        ExcelUtil<BizShareCardConfig> util = new ExcelUtil<BizShareCardConfig>(BizShareCardConfig.class);
        util.exportExcel(response, list, "分享卡片配置数据");
    }

    /**
     * 获取分享卡片配置详细信息
     */
    @PreAuthorize("@ss.hasPermi('biz:bizShareCardConfig:query')")
    @GetMapping(value = "/{id}")
    public AjaxResult getInfo(@PathVariable("id") String id)
    {
        return success(bizShareCardConfigService.selectBizShareCardConfigById(id));
    }

    /**
     * 新增分享卡片配置
     */
    @PreAuthorize("@ss.hasPermi('biz:bizShareCardConfig:add')")
    @Log(title = "分享卡片配置", businessType = BusinessType.INSERT)
    @PostMapping
    public AjaxResult add(@RequestBody BizShareCardConfig bizShareCardConfig)
    {
        return toAjax(bizShareCardConfigService.insertBizShareCardConfig(bizShareCardConfig));
    }

    /**
     * 修改分享卡片配置
     */
    @PreAuthorize("@ss.hasPermi('biz:bizShareCardConfig:edit')")
    @Log(title = "分享卡片配置", businessType = BusinessType.UPDATE)
    @PutMapping
    public AjaxResult edit(@RequestBody BizShareCardConfig bizShareCardConfig)
    {
        return toAjax(bizShareCardConfigService.updateBizShareCardConfig(bizShareCardConfig));
    }

    /**
     * 删除分享卡片配置
     */
    @PreAuthorize("@ss.hasPermi('biz:bizShareCardConfig:remove')")
    @Log(title = "分享卡片配置", businessType = BusinessType.DELETE)
	@DeleteMapping("/{ids}")
    public AjaxResult remove(@PathVariable String[] ids)
    {
        return toAjax(bizShareCardConfigService.deleteBizShareCardConfigByIds(ids));
    }
}
