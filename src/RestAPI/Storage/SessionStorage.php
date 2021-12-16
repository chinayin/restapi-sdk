<?php

namespace RestAPI\Storage;

/**
 * Session Based Storage
 * `$_SESSION` based key-value storage for persistence.
 * Note that PHP stores session data by default in local file, which
 * means the storage data will only be available to local server
 * instance. In a distributed deployment, requests of same user might
 * be routed to different instances, where session data might be not
 * avaialible. In such cases this session based storage should __not__
 * be used.
 */
class SessionStorage implements IStorage
{
    /**
     * Storage Key
     * Value will be stored under this key in $_SESSION.
     *
     * @var string
     */
    private static $storageKey = "TData";

    /**
     * Initialize session storage
     */
    public function __construct()
    {
        if (!isset($_SESSION[static::$storageKey])) {
            $_SESSION[static::$storageKey] = [];
        }
    }

    /**
     * Set value by key
     *
     * @param string $key
     * @param mixed  $val
     *
     * @return IStorage|void
     */
    public function set(string $key, $val)
    {
        $_SESSION[static::$storageKey][$key] = $val;
    }

    /**
     * Get value by key
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get(string $key)
    {
        if (isset($_SESSION[static::$storageKey][$key])) {
            return $_SESSION[static::$storageKey][$key];
        }
        return null;
    }

    /**
     * Remove key from storage
     *
     * @param string $key
     *
     * @return IStorage|void
     */
    public function remove(string $key)
    {
        unset($_SESSION[static::$storageKey][$key]);
    }

    /**
     * Clear all data in storage
     *
     * @return IStorage|void
     */
    public function clear()
    {
        $_SESSION[static::$storageKey] = [];
    }
}
