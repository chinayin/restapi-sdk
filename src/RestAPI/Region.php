<?php

namespace RestAPI;

abstract class Region
{
    // 本地环境
    public const DEV = 0;
    // 测试环境
    public const TESTING = 1;
    // 预上线环境
    public const UAT = 2;

    // 地域
    public const CN = 11;
    public const HK = 12;
    public const US = 13;
    public const GB = 14;

    /**
     * Create region from name, such as `CN`, `HK`.
     *
     * @param $name
     *
     * @return mixed
     */
    public static function fromName($name)
    {
        return constant(self::class . '::' . strtoupper($name));
    }
}
