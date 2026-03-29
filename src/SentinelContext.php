<?php

namespace UpgradeLabs\SentinelLaravel;

class SentinelContext
{
    /** @var array */
    protected static $context = [];

    /** @var array */
    protected static $breadcrumbs = [];

    /** @var int */
    protected static $maxBreadcrumbs = 50;

    /**
     * Add custom context that will be attached to error reports.
     *
     * @param  array  $data
     * @return void
     */
    public static function set(array $data)
    {
        static::$context = array_merge(static::$context, $data);
    }

    /**
     * Get all custom context.
     *
     * @return array
     */
    public static function get()
    {
        return static::$context;
    }

    /**
     * Clear all custom context.
     *
     * @return void
     */
    public static function clear()
    {
        static::$context = [];
    }

    /**
     * Add a breadcrumb.
     *
     * @param  string  $category
     * @param  string  $message
     * @param  array  $data
     * @return void
     */
    public static function breadcrumb($category, $message, array $data = [])
    {
        static::$breadcrumbs[] = [
            'category' => $category,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d\TH:i:s.u'),
        ];

        if (count(static::$breadcrumbs) > static::$maxBreadcrumbs) {
            static::$breadcrumbs = array_slice(static::$breadcrumbs, -static::$maxBreadcrumbs);
        }
    }

    /**
     * Get all breadcrumbs.
     *
     * @return array
     */
    public static function getBreadcrumbs()
    {
        return static::$breadcrumbs;
    }

    /**
     * Clear all breadcrumbs.
     *
     * @return void
     */
    public static function clearBreadcrumbs()
    {
        static::$breadcrumbs = [];
    }

    /**
     * Reset all context and breadcrumbs.
     *
     * @return void
     */
    public static function flush()
    {
        static::$context = [];
        static::$breadcrumbs = [];
    }
}
