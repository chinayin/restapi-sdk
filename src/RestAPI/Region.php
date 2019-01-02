<?php

namespace RestAPI;

abstract class Region {
    // 本地环境
    const DEV = 0;
    // 测试环境
    const TESTING = 1;
    // 预上线环境
    const PERVIEW = 2;

    // 地域
    const CN = 11;
    const HK = 12;
    const US = 13;

    /**
     * Create region from name, such as `CN`, `HK`.
     *
     * @param $name
     *
     * @return mixed
     */
    public static function fromName($name) {
        return constant(self::class.'::'.strtoupper($name));
    }
}
