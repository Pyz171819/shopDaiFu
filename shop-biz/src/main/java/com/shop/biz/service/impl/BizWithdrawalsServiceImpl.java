package com.shop.biz.service.impl;

import java.math.BigDecimal;
import java.util.Date;
import java.util.List;

import com.shop.biz.domain.BizMoneyLog;
import com.shop.biz.service.IBizMoneyLogService;
import com.shop.common.core.domain.entity.SysUser;
import com.shop.common.exception.ServiceException;
import com.shop.common.utils.DateUtils;
import com.shop.common.utils.SecurityUtils;
import com.shop.common.utils.StringUtils;
import com.shop.system.mapper.SysUserMapper;
import com.shop.system.service.ISysUserService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import com.shop.biz.mapper.BizWithdrawalsMapper;
import com.shop.biz.domain.BizWithdrawals;
import com.shop.biz.service.IBizWithdrawalsService;
import org.springframework.transaction.annotation.Transactional;

/**
 * 提现申请Service业务层处理
 * 
 * @author shop
 * @date 2026-04-17
 */
@Service
public class BizWithdrawalsServiceImpl implements IBizWithdrawalsService 
{
    @Autowired
    private BizWithdrawalsMapper bizWithdrawalsMapper;
    @Autowired
    private ISysUserService  sysUserService;
    @Autowired
    private IBizMoneyLogService bizMoneyLogService;

    /**
     * 查询提现申请
     * 
     * @param id 提现申请主键
     * @return 提现申请
     */
    @Override
    public BizWithdrawals selectBizWithdrawalsById(Long id)
    {
        return bizWithdrawalsMapper.selectBizWithdrawalsById(id);
    }

    /**
     * 查询提现申请列表
     * 
     * @param bizWithdrawals 提现申请
     * @return 提现申请
     */
    @Override
    public List<BizWithdrawals> selectBizWithdrawalsList(BizWithdrawals bizWithdrawals)
    {
        return bizWithdrawalsMapper.selectBizWithdrawalsList(bizWithdrawals);
    }

    @Override
    public List<BizWithdrawals> listAllByUserId(BizWithdrawals bizWithdrawals)
    {
        return bizWithdrawalsMapper.listAllByUserId(bizWithdrawals);
    }

    /**
     * 新增提现申请
     * 
     * @param bizWithdrawals 提现申请
     * @return 结果
     */
    @Override
    public int insertBizWithdrawals(BizWithdrawals bizWithdrawals)
    {

        //判断提现金额是否大于用户余额
        SysUser sysUser = sysUserService.selectUserById(bizWithdrawals.getUserId());
        if (sysUser == null) {
            throw new ServiceException("用户不存在");
        }
        if (sysUser.getBizUser() == null) {
            throw new ServiceException("用户业务信息不存在");
        }

        BigDecimal balance = sysUser.getBizUser().getBalance();
        BigDecimal money = bizWithdrawals.getMoney();

        if (balance == null) {
            throw new ServiceException("用户余额为空");
        }
        if (money == null) {
            throw new ServiceException("提现金额不能为空");
        }
        if (money.compareTo(BigDecimal.ZERO) <= 0) {
            throw new ServiceException("提现金额必须大于0");
        }

        if (balance.compareTo(money) < 0) {
            throw new ServiceException("提现金额不能大于用户余额");
        }

        //设置提现单号（当前时间戳+用户id）
        bizWithdrawals.setWithdrawNo(String.valueOf(System.currentTimeMillis()) + bizWithdrawals.getUserId());
        bizWithdrawals.setCreateBy(String.valueOf(SecurityUtils.getUserId()));
        bizWithdrawals.setCreateTime(DateUtils.getNowDate());
        bizWithdrawals.setStatus("0");
        bizWithdrawals.setDelFlag("0");

        //提前减去用户余额，防止无限提现
        sysUser.getBizUser().setBalance(balance.subtract(money));
        sysUserService.updateUser(sysUser);

        BizMoneyLog moneyLog = new BizMoneyLog();
        moneyLog.setUserId(bizWithdrawals.getUserId());
        moneyLog.setType("提现申请预扣资金（通过扣除，驳回退回）");
        moneyLog.setMoney(money);
        moneyLog.setBeforeMoney(balance);
        //moeny-balance
        BigDecimal afterMoney = balance.subtract(money);
        moneyLog.setAfterMoney(afterMoney);
        moneyLog.setRemark("提现申请预扣资金（通过扣除，驳回退回），提现单号：" + (bizWithdrawals.getWithdrawNo()));
        bizMoneyLogService.insertBizMoneyLog(moneyLog);

        return bizWithdrawalsMapper.insertBizWithdrawals(bizWithdrawals);
    }

    /**
     * 修改提现申请
     * 
     * @param bizWithdrawals 提现申请
     * @return 结果
     */
    @Override
    public int updateBizWithdrawals(BizWithdrawals bizWithdrawals)
    {
        bizWithdrawals.setUpdateBy(String.valueOf(SecurityUtils.getUserId()));
        bizWithdrawals.setUpdateTime(DateUtils.getNowDate());
        return bizWithdrawalsMapper.updateBizWithdrawals(bizWithdrawals);
    }

    /**
     * 批量删除提现申请
     * 
     * @param ids 需要删除的提现申请主键
     * @return 结果
     */
    @Override
    public int deleteBizWithdrawalsByIds(Long[] ids)
    {
        return bizWithdrawalsMapper.deleteBizWithdrawalsByIds(ids);
    }

    /**
     * 删除提现申请信息
     * 
     * @param id 提现申请主键
     * @return 结果
     */
    @Override
    public int deleteBizWithdrawalsById(Long id)
    {
        return bizWithdrawalsMapper.deleteBizWithdrawalsById(id);
    }


    @Override
    @Transactional
    public int auditBizWithdrawals(BizWithdrawals bo) {
        System.out.println("提现审核业务实现");

        if (bo.getId() == null) {
            throw new ServiceException("提现ID不能为空");
        }

        if (!"1".equals(bo.getStatus()) && !"2".equals(bo.getStatus())) {
            throw new ServiceException("审核状态不正确");
        }

        if ("2".equals(bo.getStatus()) && StringUtils.isEmpty(bo.getReason())) {
            throw new ServiceException("驳回时必须填写驳回原因");
        }

        BizWithdrawals db = bizWithdrawalsMapper.selectBizWithdrawalsById(bo.getId());
        if (db == null || "1".equals(db.getDelFlag())) {
            throw new ServiceException("提现记录不存在");
        }

        if (!"0".equals(db.getStatus())) {
            throw new ServiceException("只有待审核状态才可以审核");
        }

        bo.setAuditBy(String.valueOf(SecurityUtils.getUserId()));
        bo.setAuditTime(DateUtils.getNowDate());
        bo.setPayBy(String.valueOf(SecurityUtils.getUserId()));
        bo.setPayTime(DateUtils.getNowDate());
        bo.setUpdateBy(String.valueOf(SecurityUtils.getUserId()));
        bo.setUpdateTime(DateUtils.getNowDate());

        int rows = bizWithdrawalsMapper.updateBizWithdrawals(bo);
        if (rows <= 0) {
            throw new ServiceException("审核失败，请刷新后重试");
        }

        // 把余额退回去
        if ("2".equals(bo.getStatus())) {
            refundBalance(db);
        }

        return 1;
    }

    /**
     * 驳回后退回余额
     */
    private void refundBalance(BizWithdrawals db) {
        SysUser sysUser = sysUserService.selectUserById(db.getUserId());
        if (sysUser == null || sysUser.getBizUser() == null) {
            throw new ServiceException("用户信息不存在，无法退回余额");
        }

        BigDecimal beforeMoney = sysUser.getBizUser().getBalance();
        if (beforeMoney == null) {
            beforeMoney = BigDecimal.ZERO;
        }

        BigDecimal withdrawMoney = db.getMoney();
        if (withdrawMoney == null) {
            withdrawMoney = BigDecimal.ZERO;
        }

        BigDecimal afterMoney = beforeMoney.add(withdrawMoney);
        sysUser.getBizUser().setBalance(afterMoney);

        int rows = sysUserService.updateUser(sysUser);
        if (rows <= 0) {
            throw new ServiceException("退回余额失败");
        }

        BizMoneyLog moneyLog = new BizMoneyLog();
        moneyLog.setUserId(db.getUserId());
        moneyLog.setType("提现申请驳回退回资金");
        moneyLog.setMoney(withdrawMoney);
        moneyLog.setBeforeMoney(beforeMoney);
        moneyLog.setAfterMoney(afterMoney);
        moneyLog.setRemark("提现驳回退回余额，提现单号：" + (db.getWithdrawNo()));
        bizMoneyLogService.insertBizMoneyLog(moneyLog);
    }
}
