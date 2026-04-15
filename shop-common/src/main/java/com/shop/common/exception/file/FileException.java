package com.shop.common.exception.file;

import com.shop.common.exception.base.BaseException;

/**
 * 文件信息异常类
 * 
 * @author shop
 */
public class FileException extends BaseException
{
    private static final long serialVersionUID = 1L;

    public FileException(String code, Object[] args)
    {
        super("file", code, args, null);
    }

}
