package com.shop.biz.service.impl;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.shop.biz.domain.BizOrder;
import com.shop.biz.domain.vo.DashboardOverviewVo;
import com.shop.biz.domain.vo.DashboardProductVo;
import com.shop.biz.domain.vo.DashboardRevenuePointVo;
import com.shop.biz.domain.vo.DashboardUserVo;
import com.shop.biz.mapper.BizOrderMapper;
import com.shop.biz.service.IBizDashboardService;
import com.shop.common.core.domain.entity.SysUser;
import com.shop.system.mapper.SysUserMapper;
import org.apache.commons.lang3.StringUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;

import java.math.BigDecimal;
import java.math.RoundingMode;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.time.ZoneId;
import java.time.format.DateTimeFormatter;
import java.util.ArrayList;
import java.util.Comparator;
import java.util.Date;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

@Service
public class BizDashboardServiceImpl implements IBizDashboardService {

    private static final String STATUS_PAID = "1";
    private static final String STATUS_UNPAID = "0";
    private static final DateTimeFormatter DAY_LABEL_FORMATTER = DateTimeFormatter.ofPattern("MM-dd");
    private static final DateTimeFormatter USER_TIME_FORMATTER = DateTimeFormatter.ofPattern("MM-dd HH:mm");

    @Autowired
    private BizOrderMapper bizOrderMapper;

    @Autowired
    private SysUserMapper sysUserMapper;

    private final ObjectMapper objectMapper = new ObjectMapper();

    @Override
    public DashboardOverviewVo getOverview() {
        DashboardOverviewVo overview = new DashboardOverviewVo();

        LocalDate today = LocalDate.now();
        Date todayStart = toDate(today.atStartOfDay());
        Date tomorrowStart = toDate(today.plusDays(1).atStartOfDay());
        Date yesterdayStart = toDate(today.minusDays(1).atStartOfDay());

        long todayOrders = valueOrZero(bizOrderMapper.countOrdersBetween(todayStart, tomorrowStart, null));
        long todayPaidOrders = valueOrZero(bizOrderMapper.countOrdersBetween(todayStart, tomorrowStart, STATUS_PAID));

        overview.setTodayIncome(defaultAmount(bizOrderMapper.sumPaidAmountBetween(todayStart, tomorrowStart)));
        overview.setYesterdayIncome(defaultAmount(bizOrderMapper.sumPaidAmountBetween(yesterdayStart, todayStart)));
        overview.setTodayOrders(todayOrders);
        overview.setConversionRate(calculateRate(todayPaidOrders, todayOrders));
        overview.setTotalUsers(valueOrZero(sysUserMapper.countActiveUsers()));
        overview.setTodayNewUsers(valueOrZero(sysUserMapper.countUsersCreatedBetween(todayStart, tomorrowStart)));
        overview.setPendingOrders(valueOrZero(bizOrderMapper.countOrdersByStatus(STATUS_UNPAID)));
        overview.setPaidOrders(valueOrZero(bizOrderMapper.countOrdersByStatus(STATUS_PAID)));
        overview.setUnpaidOrders(valueOrZero(bizOrderMapper.countOrdersByStatus(STATUS_UNPAID)));

        fillRevenueTrend(overview, today);
        overview.setTopProducts(buildTopProducts());
        overview.setLatestUsers(buildLatestUsers());

        return overview;
    }

    private void fillRevenueTrend(DashboardOverviewVo overview, LocalDate today) {
        LocalDate startDate = today.minusDays(6);
        Date startTime = toDate(startDate.atStartOfDay());
        Date endTime = toDate(today.plusDays(1).atStartOfDay());
        List<DashboardRevenuePointVo> points = bizOrderMapper.selectRevenueTrend(startTime, endTime);
        Map<String, BigDecimal> amountMap = new LinkedHashMap<>();

        for (DashboardRevenuePointVo point : points) {
            amountMap.put(point.getDayLabel(), defaultAmount(point.getAmount()));
        }

        List<String> dates = new ArrayList<>();
        List<BigDecimal> values = new ArrayList<>();
        for (int i = 0; i < 7; i++) {
            LocalDate current = startDate.plusDays(i);
            String key = current.format(DAY_LABEL_FORMATTER);
            dates.add(key);
            values.add(amountMap.getOrDefault(key, BigDecimal.ZERO));
        }

        overview.setRevenueDates(dates);
        overview.setRevenueValues(values);
    }

    private List<DashboardProductVo> buildTopProducts() {
        Map<String, ProductAccumulator> productMap = new LinkedHashMap<>();
        List<BizOrder> orders = bizOrderMapper.selectPaidOrdersForDashboard();

        for (BizOrder order : orders) {
            if (StringUtils.isBlank(order.getItems())) {
                continue;
            }
            try {
                JsonNode itemsNode = objectMapper.readTree(order.getItems());
                if (!itemsNode.isArray()) {
                    continue;
                }
                for (JsonNode itemNode : itemsNode) {
                    String name = itemNode.path("name").asText("");
                    if (StringUtils.isBlank(name)) {
                        continue;
                    }
                    ProductAccumulator accumulator = productMap.computeIfAbsent(name, key -> new ProductAccumulator());
                    accumulator.name = name;
                    accumulator.sales += itemNode.path("quantity").asInt(0);
                    accumulator.amount = accumulator.amount.add(readAmount(itemNode));
                }
            } catch (Exception ignored) {
            }
        }

        List<DashboardProductVo> products = new ArrayList<>();
        productMap.values().stream()
            .sorted(Comparator.comparingInt(ProductAccumulator::getSales).reversed()
                .thenComparing(ProductAccumulator::getAmount, Comparator.reverseOrder()))
            .limit(5)
            .forEach(item -> {
                DashboardProductVo product = new DashboardProductVo();
                product.setName(item.name);
                product.setSales(item.sales);
                product.setAmount(item.amount);
                products.add(product);
            });

        return products;
    }

    private List<DashboardUserVo> buildLatestUsers() {
        List<DashboardUserVo> users = new ArrayList<>();
        List<SysUser> latestUsers = sysUserMapper.selectLatestUsers(5);

        for (SysUser user : latestUsers) {
            DashboardUserVo item = new DashboardUserVo();
            item.setName(StringUtils.defaultIfBlank(user.getNickName(), user.getUserName()));
            item.setIp(StringUtils.defaultIfBlank(user.getLoginIp(), "-"));
            item.setTime(user.getCreateTime() == null ? "-" : USER_TIME_FORMATTER.format(
                user.getCreateTime().toInstant().atZone(ZoneId.systemDefault()).toLocalDateTime()
            ));
            users.add(item);
        }

        return users;
    }

    private BigDecimal calculateRate(long numerator, long denominator) {
        if (denominator <= 0) {
            return BigDecimal.ZERO;
        }
        return BigDecimal.valueOf(numerator)
            .multiply(new BigDecimal("100"))
            .divide(BigDecimal.valueOf(denominator), 2, RoundingMode.HALF_UP);
    }

    private BigDecimal readAmount(JsonNode itemNode) {
        JsonNode totalAmountNode = itemNode.path("totalAmount");
        if (!totalAmountNode.isMissingNode() && !totalAmountNode.isNull() && StringUtils.isNotBlank(totalAmountNode.asText())) {
            return new BigDecimal(totalAmountNode.asText("0"));
        }
        BigDecimal price = new BigDecimal(itemNode.path("price").asText("0"));
        int quantity = itemNode.path("quantity").asInt(0);
        return price.multiply(BigDecimal.valueOf(quantity));
    }

    private long valueOrZero(Long value) {
        return value == null ? 0L : value;
    }

    private BigDecimal defaultAmount(BigDecimal amount) {
        return amount == null ? BigDecimal.ZERO : amount;
    }

    private Date toDate(LocalDateTime dateTime) {
        return Date.from(dateTime.atZone(ZoneId.systemDefault()).toInstant());
    }

    private static class ProductAccumulator {
        private String name;
        private int sales;
        private BigDecimal amount = BigDecimal.ZERO;

        public int getSales() {
            return sales;
        }

        public BigDecimal getAmount() {
            return amount;
        }
    }
}
