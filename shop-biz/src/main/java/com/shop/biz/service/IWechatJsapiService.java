package com.shop.biz.service;

import com.shop.biz.domain.vo.WechatJsapiSignatureVo;

public interface IWechatJsapiService {

    WechatJsapiSignatureVo getJsapiSignature(String url);
}