<?php

declare(strict_types=1);

namespace Youvision\ThinkTimer;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * 按 PHP 当前默认时区获取自然周期，返回 [start, end) 秒级时间戳。
 *
 * 所有范围均包含 start、不包含 end，end 为下一周期的起点。
 */
final class TimeRange
{
    /**
     * 获取基准时间所在的自然周期范围，可向前或向后偏移若干周期。
     *
     * @param string $unit day / week / month / quarter / year，周一为每周起点
     * @param mixed $datetime 基准时间，默认当前时间
     * @param int $offset 周期偏移，-1 为上一周期，1 为下一周期
     * @return array{start: int, end: int}
     * @throws InvalidArgumentException 周期单位不支持或基准时间无法解析
     */
    public static function period(string $unit, $datetime = null, int $offset = 0): array
    {
        $steps = [
            'day' => 1,
            'week' => 1,
            'month' => 1,
            'quarter' => 3,
            'year' => 1,
        ];

        if (!isset($steps[$unit])) {
            throw new InvalidArgumentException('Unsupported period unit');
        }

        $date = (new DateTimeImmutable())
            ->setTimestamp(self::timestamp($datetime))
            ->setTime(0, 0, 0);

        if ($unit === 'week') {
            $date = $date->modify('-' . ((int) $date->format('N') - 1) . ' days');
        } elseif ($unit === 'month' || $unit === 'quarter') {
            $month = (int) $date->format('n');

            if ($unit === 'quarter') {
                $month = (int) (floor(($month - 1) / 3) * 3 + 1);
            }

            $date = $date->setDate((int) $date->format('Y'), $month, 1);
        } elseif ($unit === 'year') {
            $date = $date->setDate((int) $date->format('Y'), 1, 1);
        }

        $step = $steps[$unit];
        $interval = $unit === 'quarter' ? 'month' : $unit;

        if ($offset !== 0) {
            $date = $date->modify(sprintf('%+d %s', $offset * $step, $interval));
        }

        return [
            'start' => $date->getTimestamp(),
            'end' => $date->modify('+' . $step . ' ' . $interval)->getTimestamp(),
        ];
    }

    /**
     * 获取自然日范围，从当日零点到次日零点。
     *
     * @return array{start: int, end: int}
     * @throws InvalidArgumentException
     */
    public static function day($datetime = null, int $offset = 0): array
    {
        return self::period('day', $datetime, $offset);
    }

    /**
     * 获取自然周范围，从周一零点到下周一零点。
     *
     * @return array{start: int, end: int}
     * @throws InvalidArgumentException
     */
    public static function week($datetime = null, int $offset = 0): array
    {
        return self::period('week', $datetime, $offset);
    }

    /**
     * 获取自然月范围，从当月 1 日零点到下月 1 日零点。
     *
     * @return array{start: int, end: int}
     * @throws InvalidArgumentException
     */
    public static function month($datetime = null, int $offset = 0): array
    {
        return self::period('month', $datetime, $offset);
    }

    /**
     * 获取自然季度范围，从 1、4、7、10 月的 1 日零点开始。
     *
     * @return array{start: int, end: int}
     * @throws InvalidArgumentException
     */
    public static function quarter($datetime = null, int $offset = 0): array
    {
        return self::period('quarter', $datetime, $offset);
    }

    /**
     * 获取自然年范围，从当年 1 月 1 日零点到次年 1 月 1 日零点。
     *
     * @return array{start: int, end: int}
     * @throws InvalidArgumentException
     */
    public static function year($datetime = null, int $offset = 0): array
    {
        return self::period('year', $datetime, $offset);
    }

    /**
     * 判断指定时间是否位于任意区间内，默认包含起点、不包含终点。
     *
     * @param mixed $datetime 待判断时间，null 表示当前时间
     * @param mixed $start 区间起点
     * @param mixed $end 区间终点
     * @param bool $includeStart 是否包含起点
     * @param bool $includeEnd 是否包含终点
     * @return bool
     * @throws InvalidArgumentException 起点晚于终点或日期无法解析
     */
    public static function contains(
        $datetime,
        $start,
        $end,
        bool $includeStart = true,
        bool $includeEnd = false
    ): bool {
        $timestamp = self::timestamp($datetime);
        $startTimestamp = self::timestamp($start);
        $endTimestamp = self::timestamp($end);

        if ($startTimestamp > $endTimestamp) {
            throw new InvalidArgumentException('Range start must not exceed end');
        }

        return ($includeStart ? $timestamp >= $startTimestamp : $timestamp > $startTimestamp)
            && ($includeEnd ? $timestamp <= $endTimestamp : $timestamp < $endTimestamp);
    }

    /**
     * 整数直接作为 Unix 时间戳，避免周期边界跨出宽松解析器的年份范围后无法回传。
     *
     * @param mixed $datetime
     * @throws InvalidArgumentException
     */
    private static function timestamp($datetime): int
    {
        return is_int($datetime) ? $datetime : Timer::toTimestamp($datetime);
    }
}