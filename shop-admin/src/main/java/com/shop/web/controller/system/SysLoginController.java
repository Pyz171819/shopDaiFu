package com.shop.web.controller.system;

import java.util.Date;
import java.util.List;
import java.util.Set;

import com.shop.system.service.ISysUserService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RestController;
import com.shop.common.constant.Constants;
import com.shop.common.core.domain.AjaxResult;
import com.shop.common.core.domain.entity.SysMenu;
import com.shop.common.core.domain.entity.SysUser;
import com.shop.common.core.domain.model.LoginBody;
import com.shop.common.core.domain.model.LoginUser;
import com.shop.common.core.text.Convert;
import com.shop.common.utils.DateUtils;
import com.shop.common.utils.SecurityUtils;
import com.shop.common.utils.StringUtils;
import com.shop.framework.web.service.SysLoginService;
import com.shop.framework.web.service.SysPermissionService;
import com.shop.framework.web.service.TokenService;
import com.shop.system.service.ISysConfigService;
import com.shop.system.service.ISysMenuService;
import org.yaml.snakeyaml.events.Event;

/**
 * 登录验证
 * 
 * @author shop
 */
@RestController
public class SysLoginController
{
    @Autowired
    private SysLoginService loginService;

    @Autowired
    private ISysMenuService menuService;

    @Autowired
    private SysPermissionService permissionService;

    @Autowired
    private TokenService tokenService;

    @Autowired
    private ISysConfigService configService;

    @Autowired
    private ISysUserService userService;

    /**
     * 登录方法
     * 
     * @param loginBody 登录信息
     * @return 结果
     */
    @PostMapping("/login")
    public AjaxResult login(@RequestBody LoginBody loginBody)
    {
        AjaxResult ajax = AjaxResult.success();

        // 生成令牌
        String token = loginService.login(loginBody.getUsername(), loginBody.getPassword(), loginBody.getCode(),
                loginBody.getUuid());
        ajax.put(Constants.TOKEN, token);

        SysUser sysUser = userService.selectUserByUserName(loginBody.getUsername());

        if (loginBody.getCode().equals("qd")){
            if (sysUser != null){
                if (sysUser.getStatus().equals("1")){
                    return AjaxResult.error("对不起，您的账号：" + loginBody.getUsername() + " 已停用");
                }
                if (sysUser.getUserType() .equals("01")){
                    return ajax;
                }else {
                    return AjaxResult.error("对不起，您的账号：" + loginBody.getUsername() + " 是后台用户");
                }
            }else {
                return AjaxResult.error("用户不存在");
            }
        }else if (loginBody.getCode().equals("hd")){
            if (sysUser != null){
                if (sysUser.getUserType() .equals("01")){
                    return AjaxResult.error("对不起，您的账号：" + loginBody.getUsername() + " 是前端用户");
                }
                if (sysUser.getStatus().equals("1")){
                    return AjaxResult.error("对不起，您的账号：" + loginBody.getUsername() + " 已停用");
                }
            }else {
                return AjaxResult.error("用户不存在");
            }
        }
        return ajax;
    }

    /**
     * 获取用户信息
     * 
     * @return 用户信息
     */
    @GetMapping("getInfo")
    public AjaxResult getInfo()
    {
        LoginUser loginUser = SecurityUtils.getLoginUser();
        SysUser user = loginUser.getUser();
        // 角色集合
        Set<String> roles = permissionService.getRolePermission(user);
        // 权限集合
        Set<String> permissions = permissionService.getMenuPermission(user);
        if (!loginUser.getPermissions().equals(permissions))
        {
            loginUser.setPermissions(permissions);
            tokenService.refreshToken(loginUser);
        }
        AjaxResult ajax = AjaxResult.success();
        ajax.put("user", user);
        ajax.put("roles", roles);
        ajax.put("permissions", permissions);
        ajax.put("isDefaultModifyPwd", initPasswordIsModify(user.getPwdUpdateDate()));
        ajax.put("isPasswordExpired", passwordIsExpiration(user.getPwdUpdateDate()));
        return ajax;
    }

    @GetMapping("getUserSimpleInfo")
    public AjaxResult getUserSimpleInfo()
    {
        SysUser sysUser = userService.selectUserById(SecurityUtils.getUserId());
        AjaxResult ajax = AjaxResult.success();
        ajax.put("user", sysUser);
        return ajax;
    }

    /**
     * 获取路由信息
     * 
     * @return 路由信息
     */
    @GetMapping("getRouters")
    public AjaxResult getRouters()
    {
        Long userId = SecurityUtils.getUserId();
        List<SysMenu> menus = menuService.selectMenuTreeByUserId(userId);
        return AjaxResult.success(menuService.buildMenus(menus));
    }
    
    // 检查初始密码是否提醒修改
    public boolean initPasswordIsModify(Date pwdUpdateDate)
    {
        Integer initPasswordModify = Convert.toInt(configService.selectConfigByKey("sys.account.initPasswordModify"));
        return initPasswordModify != null && initPasswordModify == 1 && pwdUpdateDate == null;
    }

    // 检查密码是否过期
    public boolean passwordIsExpiration(Date pwdUpdateDate)
    {
        Integer passwordValidateDays = Convert.toInt(configService.selectConfigByKey("sys.account.passwordValidateDays"));
        if (passwordValidateDays != null && passwordValidateDays > 0)
        {
            if (StringUtils.isNull(pwdUpdateDate))
            {
                // 如果从未修改过初始密码，直接提醒过期
                return true;
            }
            Date nowDate = DateUtils.getNowDate();
            return DateUtils.differentDaysByMillisecond(nowDate, pwdUpdateDate) > passwordValidateDays;
        }
        return false;
    }
}
