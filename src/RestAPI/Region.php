<?php

namespace RestAPI;

abstract class Region {
    const CN = 0;
    const HK = 1;
    const US = 2;

    /**
     * Create region from name, such as `CN`, `HK`.
     * @param $name
     *
     * @return mixed
     */
    public static function fromName($name) {
        return constant(self::class.'::'.strtoupper($name));
    }
}
