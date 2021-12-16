<?php

namespace RestAPI\Storage;

/**
 * Storage Interface
 *
 * Simple key-value storage interface for persisting session related
 * data. At SDK level, it is attached to Client, and used for
 * storing session token of a logged-in User.
 *
 */
interface IStorage
{
    /**
     * Set value by key
     *
     * @param string $key
     * @param mixed  $val
     * @return $this
     */
    public function set(string $key, $val);

    /**
     * Get value by key
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key);

    /**
     * Remove key from storage
     *
     * @param string $key
     * @return $this
     */
    public function remove(string $key);

    /**
     * Clear all data in storage
     *
     * @return $this
     */
    public function clear();
}
