<?php

declare(strict_types=1);

namespace Youvision\ThinkTimer;

use DateInterval;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use InvalidArgumentException;

/**
 * ThinkPHP 8 时间日期助手。
 *
 * 所有方法都可以传入任意格式的时间字符串、时间戳或 DateTimeInterface 对象；
 * 省略时间参数时默认使用当前时间。
 */
final class Timer
{
    /** @var string[] 常见的特殊日期格式 */
    private static array $dateFormats = [
        'Y-m-d',
        'm/d/Y',
        'd.m.Y',
        'm.d.y',
        'd/m/Y',
        'Y年m月d日',
        'Y年m月',
        'Y年m月d号',
        'Y/m/d',
        'Y.m.d',
        'Y.m',
        'F d, Y',
        'M d, Y',
        'F j, Y',
        'M j, Y',
        'F jS, Y',
        'M jS, Y',
    ];

    /** @var string[] 常见的特殊时间格式 */
    private static array $timeFormats = [
        'H',
        'H:i',
        'H:i:s',
        'H点',
        'H点i分',
        'H点i分s秒',
        'H时',
        'H时i分',
        'H时i分s秒',
        'g:i a',
        'h:i a',
    ];

    /**
     * 读取组件配置，支持点语法。
     *
     * @param string|null $key 例如 timeago.locale
     * @param mixed $default 配置不存在时的默认值
     * @return mixed
     */
    public static function config(?string $key = null, $default = null)
    {
        return $key === null ? Config::all() : Config::get($key, $default);
    }

    /**
     * 设置运行时配置，与已有配置递归合并。
     *
     * @param array<string, mixed> $config
     */
    public static function setConfig(array $config): void
    {
        Config::set($config);
    }

    /**
     * 清空运行时配置。
     */
    public static function resetConfig(): void
    {
        Config::reset();
    }

    /**
     * 判断是否为时间戳格式。
     *
     * @param mixed $timestamp 要判断的字符串或数字
     */
    public static function isTimestamp($timestamp): bool
    {
        $start = min(0, strtotime('1970-01-01 00:00:00'));
        $end = strtotime('2099-12-31 23:59:59');

        return is_numeric($timestamp) && $timestamp <= $end && $timestamp >= $start;
    }

    /**
     * 将任意时间类型的参数转为时间戳。
     *
     * 注意：m/d/y 或 d-m-y 格式的日期，如果分隔符是斜线（/），按美洲的 m/d/y 解析；
     * 如果分隔符是横杠（-）或点（.），按欧洲的 d-m-y 解析。
     *
     * @param mixed $datetime 任意格式时间，null 或空字符串表示当前时间
     * @throws InvalidArgumentException 无法解析时抛出
     */
    public static function toTimestamp($datetime = null): int
    {
        if ($datetime instanceof DateTimeInterface) {
            return $datetime->getTimestamp();
        }

        if (empty($datetime) && $datetime !== 0 && $datetime !== '0') {
            return time();
        }

        $start = min(0, strtotime('1970-01-01 00:00:00'));
        $end = strtotime('2099-12-31 23:59:59');

        if (is_numeric($datetime) && $datetime <= $end && $datetime >= $start) {
            return (int) $datetime;
        }

        if (!is_string($datetime)) {
            throw new InvalidArgumentException('Param datetime must be a timestamp or a string time');
        }

        $timestamp = strtotime($datetime);
        if ($timestamp !== false) {
            return $timestamp;
        }

        [$date, $time] = array_pad(explode(' ', $datetime, 2), 2, '');

        if ($date !== '') {
            $timeFormat = '';
            if ($time !== '') {
                foreach (self::$timeFormats as $format) {
                    if (date_create_from_format($format, $time) !== false) {
                        $timeFormat = $format;
                        break;
                    }
                }
            }

            foreach (self::$dateFormats as $format) {
                if (date_create_from_format($format, $date) === false) {
                    continue;
                }

                $datetimeFormat = $timeFormat !== '' ? $format . ' ' . $timeFormat : $format;
                $datetimeObject = date_create_from_format($datetimeFormat, $datetime);

                if ($datetimeObject !== false) {
                    return strtotime($datetimeObject->format('Y-m-d' . ($timeFormat !== '' ? ' H:i:s' : '')));
                }
            }
        }

        throw new InvalidArgumentException('Param datetime must be a timestamp or a string time');
    }

    /**
     * 按指定格式严格解析日期，未指定的字段从 1970-01-01 00:00:00 开始。
     *
     * 不接受自动进位、尾随字符或非规范补零，失败时抛出 InvalidArgumentException。
     *
     * @throws InvalidArgumentException
     */
    public static function parseExact(string $datetime, string $format = 'Y-m-d H:i:s', ?string $timezone = null): int
    {
        if ($format === '' || strpos($datetime, "\0") !== false || strpos($format, "\0") !== false) {
            throw new InvalidArgumentException('Invalid date or format');
        }

        try {
            $zone = new DateTimeZone($timezone === null ? date_default_timezone_get() : $timezone);
        } catch (Exception $e) {
            throw new InvalidArgumentException('Invalid timezone', 0, $e);
        }

        $date = DateTime::createFromFormat('!' . $format, $datetime, $zone);
        $errors = DateTime::getLastErrors();

        if (
            $date === false
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $date->format($format) !== $datetime
        ) {
            throw new InvalidArgumentException('Date does not match format');
        }

        return $date->getTimestamp();
    }

    /**
     * 判断日期是否严格匹配指定格式。
     */
    public static function isValidDate(string $datetime, string $format = 'Y-m-d', ?string $timezone = null): bool
    {
        try {
            self::parseExact($datetime, $format, $timezone);

            return true;
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * 将任意格式的时间转换为指定格式。
     *
     * @param string $format 与 date() 一致的格式化字符串
     * @param mixed $datetime 任意格式时间，默认为当前时间
     */
    public static function format(string $format = 'Y-m-d H:i:s', $datetime = null): string
    {
        return date($format, self::toTimestamp($datetime));
    }

    /**
     * 根据日期修改字符串修改指定时间，并返回修改后的时间戳。
     *
     * @param mixed $datetime 任意格式时间，默认为当前时间
     * @throws InvalidArgumentException 修改表达式无效时抛出
     */
    public static function modifyTimestamp(string $modifier, $datetime = null): int
    {
        $date = new DateTime();
        $date->setTimestamp(self::toTimestamp($datetime));
        $result = $date->modify($modifier);

        if ($result === false) {
            throw new InvalidArgumentException('Invalid date modifier');
        }

        return $result->getTimestamp();
    }

    /**
     * 返回截止到今天晚上 23:59:59 的剩余秒数。
     */
    public static function secondUntilMidnight(): int
    {
        [$year, $month, $day] = explode('-', date('Y-m-d'));

        return mktime(23, 59, 59, (int) $month, (int) $day, (int) $year) - time();
    }

    /**
     * @deprecated 请使用 secondUntilMidnight()
     */
    public static function secondEndToday(): int
    {
        return self::secondUntilMidnight();
    }

    /**
     * 返回 N 分钟的秒数。
     */
    public static function secondMinute(int $minutes = 1): int
    {
        return 60 * $minutes;
    }

    /**
     * 返回 N 小时的秒数。
     */
    public static function secondHour(int $hours = 1): int
    {
        return 3600 * $hours;
    }

    /**
     * 返回 N 天的秒数。
     */
    public static function secondDay(int $days = 1): int
    {
        return 86400 * $days;
    }

    /**
     * 返回 N 周的秒数。
     */
    public static function secondWeek(int $weeks = 1): int
    {
        return 604800 * $weeks;
    }

    /**
     * 转换为友好时间格式，如“刚刚、5分钟前、2天前、2周前、3个月前”。
     *
     * @param mixed $datetime 任意格式时间
     * @param string $lang 语言标识，支持 zh / zh-cn / en
     */
    public static function toFriendly($datetime, string $lang = 'zh-cn'): string
    {
        return TimeAgo::format($datetime, null, ['locale' => $lang]);
    }

    /**
     * 转换为友好时间格式，可指定基准时间与本次调用配置。
     *
     * @param mixed $datetime 任意格式时间
     * @param mixed $reference 基准时间，null 表示当前时间
     * @param array<string, mixed> $options 本次调用配置，例如 ['locale' => 'en']
     */
    public static function timeAgo($datetime, $reference = null, array $options = []): string
    {
        return TimeAgo::format($datetime, $reference, $options);
    }

    /**
     * 判断日期是否为今天。
     */
    public static function isToday($datetime): bool
    {
        return self::format('Y-m-d', $datetime) === self::format('Y-m-d');
    }

    /**
     * 判断日期是否为昨天。
     */
    public static function isYesterday($datetime): bool
    {
        return self::format('Y-m-d', $datetime) === self::format('Y-m-d', strtotime('-1 day'));
    }

    /**
     * 判断日期是否为明天。
     */
    public static function isTomorrow($datetime): bool
    {
        return self::format('Y-m-d', $datetime) === self::format('Y-m-d', strtotime('+1 day'));
    }

    /**
     * 判断日期是否在本周（周一 00:00:00 至下周一 00:00:00）。
     */
    public static function isThisWeek($datetime): bool
    {
        $range = TimeRange::week();

        return TimeRange::contains($datetime, $range['start'], $range['end']);
    }

    /**
     * 判断日期是否在本月。
     */
    public static function isThisMonth($datetime): bool
    {
        return self::format('Y-m', $datetime) === self::format('Y-m');
    }

    /**
     * 判断日期是否在今年。
     */
    public static function isThisYear($datetime): bool
    {
        return self::format('Y', $datetime) === self::format('Y');
    }

    /**
     * 获取指定日期是星期几，返回 1-7（周一为 1，周日为 7）。
     */
    public static function getWeekDay($datetime = null): int
    {
        return (int) self::format('N', $datetime);
    }

    /**
     * 判断是否为工作日（周一至周五，不考虑法定节假日）。
     */
    public static function isWeekday($datetime = null): bool
    {
        return in_array(self::getWeekDay($datetime), [1, 2, 3, 4, 5], true);
    }

    /**
     * 判断是否为周末（周六、周日）。
     */
    public static function isWeekend($datetime = null): bool
    {
        return in_array(self::getWeekDay($datetime), [6, 7], true);
    }

    /**
     * 返回两个日期相差的秒数（绝对值）。
     */
    public static function diffSeconds($datetime, $newDatetime = null): int
    {
        return abs(self::toTimestamp($newDatetime) - self::toTimestamp($datetime));
    }

    /**
     * 返回两个日期相差的分钟数（向下取整）。
     */
    public static function diffMinutes($datetime, $newDatetime = null): int
    {
        return (int) (self::diffSeconds($datetime, $newDatetime) / 60);
    }

    /**
     * 返回两个日期相差的小时数（向下取整）。
     */
    public static function diffHours($datetime, $newDatetime = null): int
    {
        return (int) (self::diffSeconds($datetime, $newDatetime) / 3600);
    }

    /**
     * 返回两个日期相差的天数。
     */
    public static function diffDays($datetime, $newDatetime = null): int
    {
        return (int) self::getDateDiff($datetime, $newDatetime)->days;
    }

    /**
     * 返回两个日期相差的周数（向下取整）。
     */
    public static function diffWeeks($datetime, $newDatetime = null): int
    {
        return (int) (self::diffDays($datetime, $newDatetime) / 7);
    }

    /**
     * 返回两个日期相差的月数。
     */
    public static function diffMonths($datetime, $newDatetime = null): int
    {
        $diff = self::getDateDiff($datetime, $newDatetime);

        return $diff->y * 12 + $diff->m;
    }

    /**
     * 返回两个日期相差的年数。
     */
    public static function diffYears($datetime, $newDatetime = null): int
    {
        return self::getDateDiff($datetime, $newDatetime)->y;
    }

    /**
     * 返回 N 分钟前的时间戳。
     *
     * @param mixed $datetime 从该时间开始计算，默认为当前时间
     * @param bool $round 是否取整到该分钟的 0 秒
     */
    public static function beforeMinute(int $minute = 1, $datetime = null, bool $round = false): int
    {
        $timestamp = self::modifyTimestamp(sprintf('-%d minute', $minute), $datetime);

        return $round ? strtotime(date('Y-m-d H:i:00', $timestamp)) : $timestamp;
    }

    /**
     * 返回 N 分钟后的时间戳。
     */
    public static function afterMinute(int $minute = 1, $datetime = null, bool $round = false): int
    {
        $timestamp = self::modifyTimestamp(sprintf('+%d minute', $minute), $datetime);

        return $round ? strtotime(date('Y-m-d H:i:00', $timestamp)) : $timestamp;
    }

    /**
     * 返回 N 小时前的时间戳。
     */
    public static function beforeHour(int $hour = 1, $datetime = null, bool $round = false): int
    {
        $timestamp = self::modifyTimestamp(sprintf('-%d hour', $hour), $datetime);

        return $round ? strtotime(date('Y-m-d H:00:00', $timestamp)) : $timestamp;
    }

    /**
     * 返回 N 小时后的时间戳。
     */
    public static function afterHour(int $hour = 1, $datetime = null, bool $round = false): int
    {
        $timestamp = self::modifyTimestamp(sprintf('+%d hour', $hour), $datetime);

        return $round ? strtotime(date('Y-m-d H:00:00', $timestamp)) : $timestamp;
    }

    /**
     * 返回 N 天前的时间戳。
     */
    public static function beforeDay(int $day = 1, $datetime = null, bool $round = false): int
    {
        $timestamp = self::modifyTimestamp(sprintf('-%d day', $day), $datetime);

        return $round ? strtotime(date('Y-m-d 00:00:00', $timestamp)) : $timestamp;
    }

    /**
     * 返回 N 天后的时间戳。
     */
    public static function afterDay(int $day = 1, $datetime = null, bool $round = false): int
    {
        $timestamp = self::modifyTimestamp(sprintf('+%d day', $day), $datetime);

        return $round ? strtotime(date('Y-m-d 00:00:00', $timestamp)) : $timestamp;
    }

    /**
     * 返回 N 周前的时间戳。
     */
    public static function beforeWeek(int $week = 1, $datetime = null, bool $round = false): int
    {
        $timestamp = self::modifyTimestamp(sprintf('-%d week', $week), $datetime);

        return $round ? strtotime(date('Y-m-d 00:00:00', $timestamp)) : $timestamp;
    }

    /**
     * 返回 N 周后的时间戳。
     */
    public static function afterWeek(int $week = 1, $datetime = null, bool $round = false): int
    {
        $timestamp = self::modifyTimestamp(sprintf('+%d week', $week), $datetime);

        return $round ? strtotime(date('Y-m-d 00:00:00', $timestamp)) : $timestamp;
    }

    /**
     * 返回 N 月前的时间戳。
     */
    public static function beforeMonth(int $month = 1, $datetime = null, bool $round = false): int
    {
        $timestamp = self::modifyTimestamp(sprintf('-%d month', $month), $datetime);

        return $round ? strtotime(date('Y-m-1 00:00:00', $timestamp)) : $timestamp;
    }

    /**
     * 返回 N 月后的时间戳。
     */
    public static function afterMonth(int $month = 1, $datetime = null, bool $round = false): int
    {
        $timestamp = self::modifyTimestamp(sprintf('+%d month', $month), $datetime);

        return $round ? strtotime(date('Y-m-1 00:00:00', $timestamp)) : $timestamp;
    }

    /**
     * 返回 N 年前的时间戳。
     */
    public static function beforeYear(int $year = 1, $datetime = null, bool $round = false): int
    {
        $timestamp = self::modifyTimestamp(sprintf('-%d year', $year), $datetime);

        return $round ? strtotime(date('Y-1-1 00:00:00', $timestamp)) : $timestamp;
    }

    /**
     * 返回 N 年后的时间戳。
     */
    public static function afterYear(int $year = 1, $datetime = null, bool $round = false): int
    {
        $timestamp = self::modifyTimestamp(sprintf('+%d year', $year), $datetime);

        return $round ? strtotime(date('Y-1-1 00:00:00', $timestamp)) : $timestamp;
    }

    /**
     * 获得秒级/毫秒级/微秒级/纳秒级时间戳。
     *
     * @param int $level 0 秒，1 毫秒，2 微秒，3 纳秒
     */
    public static function getTimestamp(int $level = 0): int
    {
        if ($level <= 0) {
            return time();
        }

        [$microseconds, $seconds] = explode(' ', microtime());
        $digits = $level === 1 ? 3 : ($level === 2 ? 6 : 9);
        $micro = str_pad(substr((string) $microseconds, 2, 6), 6, '0');
        $value = $seconds . substr($micro . '000', 0, $digits);
        $max = (string) PHP_INT_MAX;

        if (strlen($value) > strlen($max) || (strlen($value) === strlen($max) && strcmp($value, $max) > 0)) {
            throw new \OverflowException('Timestamp exceeds PHP_INT_MAX');
        }

        return (int) $value;
    }

    /**
     * 获得毫秒级时间戳。
     */
    public static function getMilliTimestamp(): int
    {
        return self::getTimestamp(1);
    }

    /**
     * 获得微秒级时间戳。
     */
    public static function getMicroTimestamp(): int
    {
        return self::getTimestamp(2);
    }

    /**
     * 获得纳秒级时间戳（末三位固定为 0，因为 microtime 只有微秒精度）。
     */
    public static function getNanoTimestamp(): int
    {
        return self::getTimestamp(3);
    }

    /**
     * 判断指定日期是否为闰年。
     */
    public static function isLeapYear($datetime = null): bool
    {
        return self::format('L', $datetime) === '1';
    }

    /**
     * 返回指定日期所在年份的天数。
     */
    public static function daysInYear($datetime = null): int
    {
        return self::isLeapYear($datetime) ? 366 : 365;
    }

    /**
     * 返回指定日期所在月份的天数。
     */
    public static function daysInMonth($datetime = null): int
    {
        return (int) self::format('t', $datetime);
    }

    /**
     * 不同时区的时间转换。
     *
     * @param mixed $datetime 任意格式时间，默认为当前时间
     * @throws Exception 时区无效时抛出
     */
    public static function timezoneFormat(
        string $toTimezone,
        ?string $fromTimezone = null,
        $datetime = 'now',
        string $format = 'Y-m-d H:i:s'
    ): string {
        if ($datetime instanceof DateTimeInterface) {
            $date = new DateTime();
            $date->setTimestamp($datetime->getTimestamp());
        } elseif (self::isTimestamp($datetime)) {
            $date = new DateTime();
            $date->setTimestamp((int) $datetime);
        } else {
            $fromTimezone = $fromTimezone === null ? date_default_timezone_get() : $fromTimezone;
            $date = new DateTime($datetime, new DateTimeZone($fromTimezone));
        }

        $date->setTimezone(new DateTimeZone($toTimezone));

        return $date->format($format);
    }

    /**
     * 比较两个时间的大小。
     *
     * @param mixed $datetime2 为空时与当前时间比较
     * @return int 第一个时间较大返回 1，较小返回 -1，相等返回 0
     */
    public static function compare($datetime1, $datetime2 = null): int
    {
        $timestamp1 = self::toTimestamp($datetime1);
        $timestamp2 = self::toTimestamp($datetime2);

        if ($timestamp1 > $timestamp2) {
            return 1;
        }

        return $timestamp1 < $timestamp2 ? -1 : 0;
    }

    /**
     * 获取当前时间。
     */
    public static function now(string $format = 'Y-m-d H:i:s'): string
    {
        return date($format);
    }

    /**
     * 返回指定日期的年份。
     */
    public static function getYear($datetime = null): int
    {
        return (int) self::format('Y', $datetime);
    }

    /**
     * 返回指定日期的季度（1-4）。
     */
    public static function getQuarter($datetime = null): int
    {
        return (int) ceil((int) self::format('n', $datetime) / 3);
    }

    /**
     * 返回指定日期的月份（1-12）。
     */
    public static function getMonth($datetime = null): int
    {
        return (int) self::format('m', $datetime);
    }

    /**
     * 返回指定日期是当年的第几周。
     */
    public static function getWeek($datetime = null): int
    {
        return (int) self::format('W', $datetime);
    }

    /**
     * 返回指定日期是当月的第几天。
     */
    public static function getDay($datetime = null): int
    {
        return (int) self::format('d', $datetime);
    }

    /**
     * 返回指定日期是当天的第几小时（0-23）。
     */
    public static function getHour($datetime = null): int
    {
        return (int) self::format('H', $datetime);
    }

    /**
     * 返回指定时间是当前小时的分钟数（0-59）。
     */
    public static function getMinute($datetime = null): int
    {
        return (int) self::format('i', $datetime);
    }

    /**
     * 返回指定时间是当前分钟的秒数（0-59）。
     */
    public static function getSecond($datetime = null): int
    {
        return (int) self::format('s', $datetime);
    }

    /**
     * 获取两个日期之间的 DateInterval。
     *
     * @param mixed $datetime
     * @param mixed $newDatetime
     * @throws InvalidArgumentException
     */
    private static function getDateDiff($datetime, $newDatetime = null): DateInterval
    {
        $first = date_create(self::format('Y-m-d H:i:s', $datetime));
        $second = date_create(self::format('Y-m-d H:i:s', $newDatetime));

        if ($first === false || $second === false) {
            throw new InvalidArgumentException('Unable to calculate date difference');
        }

        $diff = date_diff($first, $second);

        if ($diff === false) {
            throw new InvalidArgumentException('Unable to calculate date difference');
        }

        return $diff;
    }
}