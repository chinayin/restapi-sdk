<?php

namespace RestAPI;

class Helper
{
    private static $logger;

    /**
     * 日志记录函数.
     *
     * @param       $data
     * @param null  $key
     * @param array $options
     */
    public static function log($data, $key = null, $options = [])
    {
        if (null == self::$logger) {
            self::$logger = function_exists('__LOG_MESSAGE');
        }
        self::$logger && __LOG_MESSAGE($data, $key, $options);
    }

    /**
     * 获取环境变量值
     *
     * @access public
     *
     * @param  string $name    环境变量名（支持二级 . 号分割）
     * @param  string $default 默认值
     *
     * @return mixed
     */
    public static function getEnv($name, $default = null)
    {
        $prefix = defined('ENV_PREFIX') ? ENV_PREFIX : '';
        $result = getenv($prefix . strtoupper(str_replace('.', '_', $name)));

        if (false !== $result) {
            if ('false' === $result) {
                $result = false;
            } elseif ('true' === $result) {
                $result = true;
            }

            return $result;
        }

        return $default;
    }

}
