<?php

namespace RestAPI\Storage;

/**
 * Cookie Storage
 * Persist key value in client (web browser) cookies.
 * Notes:
 * 1. Since it uses PHP built-in setcookie, set value will fail
 *    after headers being sent.
 * 2. There are limits on number of bytes per cookie, and number of
 *    keys per client.
 * 3. And other caveats with setcookie.
 *
 * @see http://php.net/manual/en/function.setcookie.php
 */
class CookieStorage implements IStorage
{
    /**
     * Domain scope for cookie availability.
     *
     * @var string
     */
    private $domain;

    /**
     * Path scope for cookie availability.
     *
     * @var string
     */
    private $path;

    /**
     * When to expire the cookie.
     * It is number of seconds since epoch time.
     *
     * @var int
     */
    private $expireIn;

    /**
     * Initilize cookie storage
     *
     * @param int    $seconds Number of seconds to live
     * @param string $path    Cookie path scope
     * @param string $domain  Cookie domain scope
     */
    public function __construct(int $seconds = 0, string $path = "/", string $domain = null)
    {
        if ($seconds <= 0) {
            // default to 7 days from now
            $seconds = 60 * 60 * 24 * 7;
        }
        $this->expireIn = time() + $seconds;
        $this->path = $path;
        $this->domain = $domain;
    }

    /**
     * Set value by key
     *
     * @param string $key
     * @param mixed  $val
     * @param int    $seconds Number of seconds to live
     *
     * @return IStorage|void
     */
    public function set(string $key, $val, int $seconds = null)
    {
        $expire = $seconds ? (time() + $seconds) : $this->expireIn;
        setcookie($key, $val, $expire, $this->path, $this->domain);
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
        if (isset($_COOKIE[$key])) {
            return $_COOKIE[$key];
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
        setcookie($key, '', 1);
    }

    /**
     * Clear all data in storage
     *
     * @return IStorage|void
     */
    public function clear()
    {
        throw new \RuntimeException("Not implemented error.");
    }
}
