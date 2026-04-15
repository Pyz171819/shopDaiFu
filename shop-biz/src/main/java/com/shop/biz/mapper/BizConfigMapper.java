package com.shop.biz.mapper;

import org.apache.ibatis.annotations.Param;

public interface BizConfigMapper {

    /**
     * 根据 key 查询配置值
     */
    String selectValueByKey(@Param("k") String k);

    /**
     * 根据 key 统计数量
     */
    int countByKey(@Param("k") String k);

    /**
     * 新增配置
     */
    int insertConfig(@Param("k") String k, @Param("v") String v);

    /**
     * 根据 key 更新配置
     */
    int updateConfigByKey(@Param("k") String k, @Param("v") String v);
}