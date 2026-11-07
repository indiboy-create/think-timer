<?php

declare(strict_types=1);

use Youvision\ThinkTimer\Timer;

if (!function_exists('time_ago')) {
    /**
     * 转换为友好时间，例如“刚刚、5分钟前、2天前、3个月前”。
     *
     * @param mixed $datetime 任意格式时间
     * @param mixed $reference 基准时间，null 表示当前时间
     * @param array<string, mixed> $options 本次调用配置，例如 ['locale' => 'en']
     */
    function time_ago($datetime, $reference = null, array $options = []): string
    {
        return Timer::timeAgo($datetime, $reference, $options);
    }
}

if (!function_exists('time_format')) {
    /**
     * 将任意格式时间格式化为指定格式。
     *
     * @param string $format 与 date() 一致的格式化字符串
     * @param mixed $datetime 任意格式时间，null 表示当前时间
     */
    function time_format(string $format = 'Y-m-d H:i:s', $datetime = null): string
    {
        return Timer::format($format, $datetime);
    }
}