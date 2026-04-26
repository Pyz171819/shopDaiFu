package com.shop.biz.service.impl;

import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.shop.biz.domain.BizMoneyLog;
import com.shop.biz.domain.BizOrder;
import com.shop.biz.domain.BizProductSimple;
import com.shop.biz.domain.dto.OrderCreateDto;
import com.shop.biz.domain.dto.OrderItemDto;
import com.shop.biz.domain.pay.BizOrderPayReq;
import com.shop.biz.domain.pay.BizOrderPayResp;
import com.shop.biz.domain.pay.BizWxPayV2Config;
import com.shop.biz.mapper.BizConfigMapper;
import com.shop.biz.mapper.BizMoneyLogMapper;
import com.shop.biz.mapper.BizOrderMapper;
import com.shop.biz.mapper.BizProductsMapper;
import com.shop.biz.service.IBizOrderService;
import com.shop.biz.utils.WechatPayGateway;
import com.shop.common.core.domain.entity.BizUsers;
import com.shop.common.core.domain.entity.SysUser;
import com.shop.common.exception.ServiceException;
import com.shop.common.utils.DateUtils;
import com.shop.common.utils.SecurityUtils;
import com.shop.system.service.ISysUserService;
import jakarta.servlet.http.HttpServletRequest;
import org.apache.commons.lang3.StringUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.math.BigDecimal;
import java.nio.charset.StandardCharsets;
import java.security.SecureRandom;
import java.text.SimpleDateFormat;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.*;

@Service
public class BizOrderServiceImpl implements IBizOrderService {

    private static final String STATUS_UNPAID = "0";
    private static final String DEFAULT_TEMPLATE = "default";
    private static final String DEFAULT_TPL = "cashier";
    private static final String DEFAULT_PAY_CHANNEL = "wxpay";

    @Autowired
    private BizOrderMapper bizOrderMapper;

    @Autowired
    private BizProductsMapper bizProductMapper;

    @Autowired
    private BizConfigMapper bizConfigMapper;

    @Autowired
    private BizMoneyLogServiceImpl bizMoneyLogService;

    @Autowired
    private ISysUserService sysUserService;

    @Autowired
    private WechatPayGateway wechatPayGateway;
//    @Autowired
//    private ObjectMapper objectMapper;
    private final ObjectMapper objectMapper = new ObjectMapper();

    private final SecureRandom secureRandom = new SecureRandom();

    private static final String STATUS_PAID = "1";
    private static final String PAY_TYPE_WXPAY = "wxpay";



    /**
     * 查询订单列表
     *
     * @param bizOrder 订单
     * @return 订单
     */
    @Override
    public List<BizOrder> selectBizOrderList(BizOrder bizOrder)
    {
        return bizOrderMapper.selectBizOrderList(bizOrder);
    }

    /**
     * 新增订单
     *
     * @param bizOrder 订单
     * @return 结果
     */
    @Override
    public int insertBizOrder(BizOrder bizOrder)
    {
        bizOrder.setCreateTime(DateUtils.getNowDate());
        return bizOrderMapper.insertBizOrder(bizOrder);
    }

    /**
     * 修改订单
     *
     * @param bizOrder 订单
     * @return 结果
     */
    @Override
    @Transactional
    public int updateBizOrder(BizOrder bizOrder)
    {
        bizOrder.setUpdateBy(String.valueOf(SecurityUtils.getUserId()));
        bizOrder.setUpdateTime(DateUtils.getNowDate());
        //修改订单状态之前查询原订单信息
        BizOrder oldOrder = bizOrderMapper.selectBizOrderById2(bizOrder.getId());
        BizMoneyLog bizMoneyLog = new BizMoneyLog();
        //根据原订单信息状态修改订单的佣金逻辑
        //1、当前订单为待支付状态，修改为已支付(如改为已过期则直接该状态即可)
        if (STATUS_UNPAID.equals(oldOrder.getStatus())) {
            if (STATUS_PAID.equals(bizOrder.getStatus())) {
                //获取订单创建人和订单金额
                String createBy = oldOrder.getCreateBy();
                BigDecimal orderMoney = oldOrder.getMoney();
                //修改用户的balance
                SysUser sysUser = sysUserService.selectUserById(Long.valueOf(createBy));
                BigDecimal commission = oldOrder.getMoney().subtract(orderMoney.multiply(sysUser.getBizUser()
                                                           .getCommissionRate())
                                                           .divide(new BigDecimal("100"), 2, BigDecimal.ROUND_HALF_UP));
                bizMoneyLog.setBeforeMoney(sysUser.getBizUser().getBalance());
                sysUser.getBizUser().setBalance(sysUser.getBizUser().getBalance().add(commission));
                bizMoneyLog.setAfterMoney(sysUser.getBizUser().getBalance());
                sysUserService.updateUser(sysUser);
                bizMoneyLog.setUserId(Long.valueOf(createBy));
                bizMoneyLog.setMoney(commission);
                bizMoneyLog.setType("待支付状态修改为已支付，增加佣金");
                bizMoneyLog.setRemark("待支付状态修改为已支付，增加佣金");
            }
        }
        //2、当前订单为已支付状态，修改为待支付或已过期，则需要修改退回用户的balance（订单金额 * 用户金额比例）
        if (STATUS_PAID.equals(oldOrder.getStatus())) {
            if (!STATUS_PAID.equals(bizOrder.getStatus())) {
                SysUser sysUser = sysUserService.selectUserById(Long.valueOf(oldOrder.getCreateBy()));
                BigDecimal commission = oldOrder.getMoney().subtract(oldOrder.getMoney()
                        .multiply(sysUser.getBizUser().getCommissionRate())
                        .divide(new BigDecimal("100"), 2, BigDecimal.ROUND_HALF_UP));
                bizMoneyLog.setBeforeMoney(sysUser.getBizUser().getBalance());
                sysUser.getBizUser().setBalance(sysUser.getBizUser().getBalance().subtract(commission));
                bizMoneyLog.setAfterMoney(sysUser.getBizUser().getBalance());
                sysUserService.updateUser(sysUser);
                bizMoneyLog.setUserId(Long.valueOf(oldOrder.getCreateBy()));
                bizMoneyLog.setMoney(commission);
                bizMoneyLog.setType("已支付状态修改为待支付或已过期，减去佣金");
                bizMoneyLog.setRemark("已支付状态修改为待支付或已过期，减去佣金");
            }
        }
        //3、当前订单为已过期状态，修改为已支付，则需要修改增加用户的balance（订单金额 * 用户金额比例）
        if ("2".equals(oldOrder.getStatus())) {
            if (STATUS_PAID.equals(bizOrder.getStatus())) {
                SysUser sysUser = sysUserService.selectUserById(Long.valueOf(oldOrder.getCreateBy()));
                BigDecimal commission = oldOrder.getMoney().subtract(oldOrder.getMoney()
                        .multiply(sysUser.getBizUser().getCommissionRate())
                        .divide(new BigDecimal("100"), 2, BigDecimal.ROUND_HALF_UP));

                bizMoneyLog.setBeforeMoney(sysUser.getBizUser().getBalance());
                sysUser.getBizUser().setBalance(sysUser.getBizUser().getBalance().add(commission));
                bizMoneyLog.setAfterMoney(sysUser.getBizUser().getBalance());
                sysUserService.updateUser(sysUser);
                bizMoneyLog.setUserId(Long.valueOf(oldOrder.getCreateBy()));
                bizMoneyLog.setMoney(commission);
                bizMoneyLog.setType("已支付状态修改为待支付或已过期，减去佣金");
                bizMoneyLog.setRemark("已支付状态修改为待支付或已过期，减去佣金");
            }
        }
        //增加资金操作记录
        bizMoneyLogService.insertBizMoneyLog(bizMoneyLog);
        return bizOrderMapper.updateBizOrder2(bizOrder);
    }

    /**
     * 批量删除订单
     *
     * @param ids 需要删除的订单主键
     * @return 结果
     */
    @Override
    public int deleteBizOrderByIds(String[] ids)
    {
        return bizOrderMapper.deleteBizOrderByIds(ids);
    }

    /**
     * 删除订单信息
     *
     * @param id 订单主键
     * @return 结果
     */
    @Override
    public int deleteBizOrderById(String id)
    {
        return bizOrderMapper.deleteBizOrderById(id);
    }





//    ======================================================================================

    /**
     * 查询订单
     *
     * @param id 订单主键
     * @return 订单
     */
    @Override
    public BizOrder selectBizOrderById(Long id)
    {
        return bizOrderMapper.selectBizOrderById2(id);
    }

    @Override
    @Transactional(rollbackFor = Exception.class)
    public BizOrder createOrder(OrderCreateDto dto) {
        if (dto == null) {
            throw new ServiceException("请求参数不能为空");
        }
        if (dto.getItems() == null || dto.getItems().isEmpty()) {
            throw new ServiceException("订单商品不能为空");
        }

        List<OrderItemDto> normalizedItems = normalizeItems(dto.getItems());
        if (normalizedItems.isEmpty()) {
            throw new ServiceException("订单商品不能为空");
        }

        List<Map<String, Object>> snapshotItems = new ArrayList<>();
        BigDecimal totalMoney = BigDecimal.ZERO;
        Long ownerUserId = null;
        Integer singleProductId = null;
        String firstProductName = null;

        for (int i = 0; i < normalizedItems.size(); i++) {
            OrderItemDto orderItem = normalizedItems.get(i);

            if (orderItem.getProductId() == null) {
                throw new ServiceException("商品ID不能为空");
            }
            if (orderItem.getQuantity() == null || orderItem.getQuantity() <= 0) {
                throw new ServiceException("商品数量必须大于0");
            }

            BizProductSimple product = bizProductMapper.selectSimpleProductById(orderItem.getProductId());
            if (product == null) {
                throw new ServiceException("商品不存在，商品ID：" + orderItem.getProductId());
            }
            if (product.getAuditStatus() == null || product.getAuditStatus() != 1) {
                throw new ServiceException("商品未审核通过，暂不能下单：" + product.getName());
            }
            if (product.getPrice() == null || product.getPrice().compareTo(BigDecimal.ZERO) <= 0) {
                throw new ServiceException("商品价格异常：" + product.getName());
            }

            // 第一版建议同一订单下商品归属同一个用户，否则后续结算会混乱
//            if (ownerUserId == null) {
//                ownerUserId = product.getUserId();
//            } else if (!Objects.equals(ownerUserId, product.getUserId())) {
//                throw new ServiceException("暂不支持不同用户的商品合并下单");
//            }

            if (i == 0) {
                firstProductName = defaultIfBlank(product.getName(), "商品订单");
            }

            if (normalizedItems.size() == 1) {
                singleProductId = product.getId();
            }

            BigDecimal lineTotal = product.getPrice().multiply(BigDecimal.valueOf(orderItem.getQuantity()));
            totalMoney = totalMoney.add(lineTotal);

            Map<String, Object> snapshot = new LinkedHashMap<>();
            snapshot.put("id", product.getId());
            snapshot.put("name", product.getName());
            snapshot.put("price", product.getPrice());
            snapshot.put("quantity", orderItem.getQuantity());
            snapshot.put("totalAmount", lineTotal);
            snapshot.put("image", product.getImage());
            snapshot.put("shopName", product.getShopName());

            snapshotItems.add(snapshot);
        }

        BizOrder order = new BizOrder();
        order.setOutTradeNo(generateOutTradeNo());
        order.setProductId(singleProductId); // 单商品写 product_id，多商品留空
        order.setMoney(totalMoney);
        order.setStatus(STATUS_UNPAID);
        order.setPayType(null);
        order.setType(getCurrentPayChannel());
        order.setOrderName(buildOrderName(snapshotItems, firstProductName));
        order.setTemplate(defaultIfBlank(dto.getTemplate(), DEFAULT_TEMPLATE));
        order.setTpl(defaultIfBlank(dto.getTpl(), DEFAULT_TPL));
        order.setItems(toJson(snapshotItems));
        order.setExpireTime(buildExpireTime(15));
        order.setOrderCreateUserId(SecurityUtils.getUserId());
        order.setCreateBy(String.valueOf(SecurityUtils.getUserId()));
        order.setDelFlag("0");

        bizOrderMapper.insertBizOrder(order);
        return bizOrderMapper.selectBizOrderById(order.getId());
    }

    @Override
    public BizOrder getOrderById(Long id) {
        if (id == null) {
            throw new ServiceException("订单ID不能为空");
        }
        BizOrder order = bizOrderMapper.selectBizOrderById(id);
        if (order == null) {
            throw new ServiceException("订单不存在");
        }
        return order;
    }

    @Override
    public BizOrder getOrderByOutTradeNo(String outTradeNo) {
        if (StringUtils.isBlank(outTradeNo)) {
            throw new ServiceException("商户订单号不能为空");
        }
        BizOrder order = bizOrderMapper.selectBizOrderByOutTradeNo(outTradeNo.trim());
        if (order == null) {
            throw new ServiceException("订单不存在");
        }
        return order;
    }

    @Override
    public BizOrderPayResp pay(BizOrderPayReq req, HttpServletRequest request) {
        if (req == null || StringUtils.isBlank(req.getTradeNo())) {
            throw new ServiceException("订单号不能为空");
        }

        validateWechatBrowser(request);

        BizOrder order = getValidPayOrder(req.getTradeNo());

        return buildJsapiPay(order, request);
    }

    @Override
    public List<BizOrder> queryByStatus(BizOrder  order) {
         return bizOrderMapper.queryByStatus(order);
    }

    @Override
    public BigDecimal queryTodayXF(BizOrder order) {
        return bizOrderMapper.queryTodayXF(order);
    }

    @Override
    public int deleteOrder(String outTradeNo) {
        return bizOrderMapper.deleteBizOrderByOutTradeNo(outTradeNo);
    }

    @Override
    public void updateExpiredOrderStatus() {
        bizOrderMapper.updateExpiredOrderStatus();
    }

    @Override
    @Transactional
    public String handleWxPayNotify(HttpServletRequest request) {
        String xml = readRequestBody(request);
        if (StringUtils.isBlank(xml)) {
            return wxFail("通知内容为空");
        }

        Map<String, String> notifyMap = wechatPayGateway.parseXmlToMap(xml);

        String returnCode = notifyMap.get("return_code");
        if (!"SUCCESS".equals(returnCode)) {
            return wxFail(defaultIfBlank(notifyMap.get("return_msg"), "通信失败"));
        }

        String resultCode = notifyMap.get("result_code");
        if (!"SUCCESS".equals(resultCode)) {
            return wxFail(defaultIfBlank(notifyMap.get("err_code_des"), "支付失败"));
        }

        if (!wechatPayGateway.verifyNotifySign(notifyMap)) {
            return wxFail("签名失败");
        }

        String outTradeNo = notifyMap.get("out_trade_no");
        String transactionId = notifyMap.get("transaction_id");
        String totalFeeStr = notifyMap.get("total_fee");
        String openid = notifyMap.get("openid");

        if (StringUtils.isBlank(outTradeNo)) {
            return wxFail("商户订单号为空");
        }
        if (StringUtils.isBlank(totalFeeStr)) {
            return wxFail("支付金额为空");
        }

        BizOrder order = bizOrderMapper.selectBizOrderByOutTradeNo(outTradeNo);
        if (order == null || "1".equals(order.getDelFlag())) {
            return wxFail("订单不存在");
        }

        int notifyTotalFee;
        try {
            notifyTotalFee = Integer.parseInt(totalFeeStr);
        } catch (Exception e) {
            return wxFail("支付金额格式错误");
        }

        int orderTotalFee = toFen(order.getMoney());
        if (notifyTotalFee != orderTotalFee) {
            return wxFail("支付金额校验失败");
        }

        if (STATUS_PAID.equals(order.getStatus())) {
            return wxSuccess();
        }

        // 更新订单
        BizOrder update = new BizOrder();
        update.setId(order.getId());
        update.setOpenid(openid);
        update.setStatus(STATUS_PAID);
        update.setPayType(PAY_TYPE_WXPAY);
        update.setPayTime(new Date());
        update.setUpdateTime(new Date());
        update.setUpdateBy("wxpay_notify");
        update.setTransactionId(transactionId);
        update.setNotifyTime(new Date());

        int rows = bizOrderMapper.updateBizOrder(update);
        if (rows <= 0) {
            return wxFail("更新订单失败");
        }
        //更新用户资金
        SysUser sysUser = sysUserService.selectUserById(Long.valueOf(order.getCreateBy()));

        BigDecimal orderMoney = order.getMoney();
        BigDecimal commissionRate = sysUser.getBizUser().getCommissionRate();

        BigDecimal commission = orderMoney
                .multiply(commissionRate)
                .divide(new BigDecimal("100"), 2, BigDecimal.ROUND_HALF_UP);

        BigDecimal withdrawMoney = orderMoney.subtract(commission);

        BizUsers bizUser = sysUser.getBizUser();
        bizUser.setBalance(bizUser.getBalance().add(withdrawMoney));

        sysUserService.updateUserByNotice(sysUser);
        // 增加资金流动记录
        BizMoneyLog log = new BizMoneyLog();
        log.setUserId(Long.valueOf(order.getCreateBy()));
        log.setType("订单支付成功");
        log.setMoney(order.getMoney());
        log.setBeforeMoney(sysUser.getBizUser().getBalance());
        log.setAfterMoney(sysUser.getBizUser().getBalance().add(order.getMoney()));
        log.setRemark("订单支付成功");
        int rs1 = bizMoneyLogService.insertBizMoneyLogByNotify(log);
        if (rs1 <= 0) {
            return wxFail("资金日志更新失败");
        }
        return wxSuccess();
    }

    private int toFen(BigDecimal amount) {
        if (amount == null) {
            throw new ServiceException("订单金额不能为空");
        }
        return amount.multiply(new BigDecimal("100")).intValue();
    }
    private String readRequestBody(HttpServletRequest request) {
        if (request == null) {
            return null;
        }

        StringBuilder sb = new StringBuilder();
        try (BufferedReader reader = new BufferedReader(
                new InputStreamReader(request.getInputStream(), StandardCharsets.UTF_8))) {
            String line;
            while ((line = reader.readLine()) != null) {
                sb.append(line);
            }
        } catch (Exception e) {
            throw new ServiceException("读取微信通知内容失败：" + e.getMessage());
        }
        return sb.toString();
    }

    /*private boolean verifySign(Map<String, String> data, String key) {
        if (data == null || data.isEmpty() || StringUtils.isBlank(key)) {
            return false;
        }

        String sign = data.get("sign");
        if (StringUtils.isBlank(sign)) {
            return false;
        }

        SortedMap<String, String> sortedMap = new TreeMap<>();
        for (Map.Entry<String, String> entry : data.entrySet()) {
            String k = entry.getKey();
            String v = entry.getValue();
            if (!"sign".equals(k) && StringUtils.isNotBlank(v)) {
                sortedMap.put(k, v);
            }
        }

        String signType = data.get("sign_type");
        String localSign = createSign(sortedMap, key, StringUtils.isBlank(signType) ? SIGN_TYPE : signType);
        return sign.equalsIgnoreCase(localSign);
    }*/

    private String wxSuccess() {
        return "<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>";
    }

    private String wxFail(String msg) {
        return "<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA["
                + defaultIfBlank(msg, "FAIL")
                + "]]></return_msg></xml>";
    }




    private void validateWechatBrowser(HttpServletRequest request) {
        if (request == null) {
            throw new ServiceException("请求异常，请在微信内打开页面完成支付");
        }

        String ua = request.getHeader("User-Agent");
        if (StringUtils.isBlank(ua) || !ua.toLowerCase().contains("micromessenger")) {
            throw new ServiceException("请在微信内打开页面完成支付");
        }
    }
    private BizOrder getValidPayOrder(String tradeNo) {
        BizOrder order = bizOrderMapper.selectBizOrderByOutTradeNo(tradeNo.trim());
        if (order == null || "1".equals(order.getDelFlag())) {
            throw new ServiceException("订单不存在");
        }

        if ("1".equals(order.getStatus())) {
            throw new ServiceException("订单已支付，请勿重复支付");
        }

        if ("2".equals(order.getStatus())) {
            throw new ServiceException("订单已关闭");
        }

        if ("3".equals(order.getStatus())) {
            throw new ServiceException("订单已退款");
        }

        if (order.getExpireTime() != null && LocalDateTime.now().isAfter(toLocalDateTime(order.getExpireTime()))) {
            throw new ServiceException("订单已过期");
        }

        return order;
    }
    private BizOrderPayResp buildJsapiPay(BizOrder order, HttpServletRequest request) {
        String openId = getCurrentWechatOpenId(request);
        System.out.println("pay sessionId=" + request.getSession().getId() + ", openId=" + openId);

        if (StringUtils.isBlank(openId)) {
            throw new ServiceException("当前微信用户 openId 不存在，请重新从微信打开页面");
        }

        BizOrderPayResp resp = new BizOrderPayResp();
        resp.setPayScene("JSAPI");
        resp.setTradeNo(order.getOutTradeNo());
        resp.setPayParams(wechatPayGateway.jsapiPay(order, openId));
        resp.setAmount(order.getMoney().toPlainString());
        resp.setExpireTime(format(order.getExpireTime()));
        return resp;
    }


    private String getCurrentWechatOpenId(HttpServletRequest request) {
        if (request == null) {
            return null;
        }
        Object openIdObj = request.getSession().getAttribute("CURRENT_WECHAT_OPENID");
        if (openIdObj == null) {
            return null;
        }
        String openId = String.valueOf(openIdObj);
        return StringUtils.isBlank(openId) ? null : openId.trim();
    }

    private LocalDateTime toLocalDateTime(java.util.Date date) {
        return LocalDateTime.ofInstant(date.toInstant(), java.time.ZoneId.systemDefault());
    }

    private String format(java.util.Date date) {
        if (date == null) {
            return null;
        }
        return DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss")
                .format(toLocalDateTime(date));
    }




    /**
     * 合并重复商品，避免同一商品多条重复项
     */
    private List<OrderItemDto> normalizeItems(List<OrderItemDto> rawItems) {
        Map<Integer, Integer> merged = new LinkedHashMap<>();

        for (OrderItemDto item : rawItems) {
            if (item == null || item.getProductId() == null) {
                continue;
            }
            int quantity = item.getQuantity() == null ? 0 : item.getQuantity();
            if (quantity <= 0) {
                continue;
            }
            merged.put(item.getProductId(), merged.getOrDefault(item.getProductId(), 0) + quantity);
        }

        List<OrderItemDto> result = new ArrayList<>();
        for (Map.Entry<Integer, Integer> entry : merged.entrySet()) {
            OrderItemDto dto = new OrderItemDto();
            dto.setProductId(entry.getKey());
            dto.setQuantity(entry.getValue());
            result.add(dto);
        }
        return result;
    }

    private String getCurrentPayChannel() {
        String value = bizConfigMapper.selectValueByKey("pay_channel");
        return StringUtils.isBlank(value) ? DEFAULT_PAY_CHANNEL : value.trim();
    }

    private String buildOrderName(List<Map<String, Object>> snapshotItems, String firstProductName) {
        if (snapshotItems == null || snapshotItems.isEmpty()) {
            return "商品订单";
        }

        if (snapshotItems.size() == 1) {
            Map<String, Object> item = snapshotItems.get(0);
            Object quantity = item.get("quantity");
            return defaultIfBlank(firstProductName, "商品订单") + " x" + quantity;
        }

        return defaultIfBlank(firstProductName, "商品订单") + "等" + snapshotItems.size() + "件商品";
    }

    private Date buildExpireTime(int minutes) {
        Calendar calendar = Calendar.getInstance();
        calendar.add(Calendar.MINUTE, minutes);
        return calendar.getTime();
    }

    private String toJson(Object value) {
        try {
            return objectMapper.writeValueAsString(value);
        } catch (JsonProcessingException e) {
            throw new ServiceException("订单商品快照生成失败");
        }
    }

    private String generateOutTradeNo() {
        for (int i = 0; i < 10; i++) {
            String no = buildOutTradeNoCandidate();
            if (bizOrderMapper.countByOutTradeNo(no) == 0) {
                return no;
            }
        }
        throw new ServiceException("生成商户订单号失败，请稍后重试");
    }

    private String buildOutTradeNoCandidate() {
        String timePart = new SimpleDateFormat("yyyyMMddHHmmssSSS").format(new Date());
        int randomPart = 100000 + secureRandom.nextInt(900000);
        return timePart + randomPart;
    }

    private String defaultIfBlank(String value, String defaultValue) {
        return StringUtils.isBlank(value) ? defaultValue : value.trim();
    }


}