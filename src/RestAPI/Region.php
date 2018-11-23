<?php

namespace RestAPI;

abstract class Region {
    // 本地环境
    const DEV = 0;
    // 测试环境
    const TESTING = 1;

    // 地域
    const CN = 2;
    const HK = 3;
    const US = 4;

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
