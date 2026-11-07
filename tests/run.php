<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'Youvision\\ThinkTimer\\';
    $length = strlen($prefix);

    if (strncmp($class, $prefix, $length) !== 0) {
        return;
    }

    $file = dirname(__DIR__) . '/src/' . str_replace('\\', '/', substr($class, $length)) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

require dirname(__DIR__) . '/src/helpers.php';

date_default_timezone_set('Asia/Shanghai');

use Youvision\ThinkTimer\ChinaHoliday;
use Youvision\ThinkTimer\TimeRange;
use Youvision\ThinkTimer\Timer;

$passed = 0;
$failed = 0;

/**
 * @param mixed $actual
 * @param mixed $expected
 */
function check(string $name, $actual, $expected): void
{
    global $passed, $failed;

    if ($actual === $expected) {
        $passed++;

        return;
    }

    $failed++;
    fwrite(STDERR, sprintf(
        "[FAIL] %s\n  expected: %s\n  actual:   %s\n",
        $name,
        var_export($expected, true),
        var_export($actual, true)
    ));
}

function checkThrows(string $name, callable $callback): void
{
    global $passed, $failed;

    try {
        $callback();
    } catch (Throwable $e) {
        $passed++;

        return;
    }

    $failed++;
    fwrite(STDERR, "[FAIL] {$name}\n  expected exception, none thrown\n");
}

$base = Timer::toTimestamp('2026-01-01 12:00:00');

check('toTimestamp 时间戳 0 不被当成空值', Timer::toTimestamp(0), 0);
check('toTimestamp 任意字符串', Timer::toTimestamp('2022-03-02 10:15:33'), strtotime('2022-03-02 10:15:33'));
check('isTimestamp', Timer::isTimestamp('1646360133'), true);
check('isTimestamp 非时间', Timer::isTimestamp('not-a-time'), false);
check('format', Timer::format('Y-m-d H:i:s', 0), date('Y-m-d H:i:s', 0));
check('format 中文日期', Timer::format('Y-m-d', '2023年9月5日'), '2023-09-05');

check('diffDays', Timer::diffDays('2026-01-01', '2026-01-11'), 10);
check('diffWeeks', Timer::diffWeeks('2026-01-01', '2026-01-15'), 2);
check('diffMonths', Timer::diffMonths('2026-01-01', '2026-04-01'), 3);
check('diffYears', Timer::diffYears('2020-01-01', '2026-01-01'), 6);
check('beforeDay 取整', Timer::beforeDay(1, '2026-01-11 12:00:00', true), strtotime('2026-01-10 00:00:00'));
check('afterHour', Timer::afterHour(2, '2026-01-11 12:00:00'), strtotime('2026-01-11 14:00:00'));
check('beforeMonth 取整', Timer::beforeMonth(1, '2026-03-15 12:00:00', true), strtotime('2026-02-01 00:00:00'));

check('getWeekDay', Timer::getWeekDay('2026-01-05'), 1);
check('getQuarter', Timer::getQuarter('2026-05-01'), 2);
check('isLeapYear', Timer::isLeapYear('2024-01-01'), true);
check('daysInMonth', Timer::daysInMonth('2024-02-01'), 29);
check('daysInYear', Timer::daysInYear('2024-01-01'), 366);

check('parseExact', Timer::parseExact('03/04/2026', 'd/m/Y'), strtotime('2026-04-03 00:00:00'));
check('isValidDate 不存在日期', Timer::isValidDate('2026-02-31'), false);
check('isValidDate 合法日期', Timer::isValidDate('2026-02-28'), true);

check('timezoneFormat 东八区', Timer::timezoneFormat('Asia/Shanghai', 'UTC', '2026-01-01 00:00:00'), '2026-01-01 08:00:00');
check('timezoneFormat UTC', Timer::timezoneFormat('UTC', 'Asia/Shanghai', '2026-01-01 00:00:00'), '2025-12-31 16:00:00');

check('compare 大', Timer::compare('2026-01-02', '2026-01-01'), 1);
check('compare 等', Timer::compare('2026-01-01', '2026-01-01'), 0);

$milli = (string) Timer::getMilliTimestamp();
check('毫秒时间戳长度', strlen($milli), 13);

// timeago：过去
check('timeago 刚刚', Timer::timeAgo($base - 30, $base), '刚刚');
check('timeago 分钟', Timer::timeAgo($base - 120, $base), '2分钟前');
check('timeago 小时', Timer::timeAgo($base - 3 * 3600, $base), '3小时前');
check('timeago 天', Timer::timeAgo($base - 4 * 86400, $base), '4天前');
check('timeago 周', Timer::timeAgo($base - 14 * 86400, $base), '2周前');
check('timeago 月', Timer::timeAgo($base - 90 * 86400, $base), '3个月前');
check('timeago 年', Timer::timeAgo($base - 730 * 86400, $base), '2年前');
check('timeago 未来', Timer::timeAgo($base + 2 * 3600, $base), '2小时后');
check('timeago 英文', Timer::timeAgo($base - 120, $base, ['locale' => 'en']), '2 minute(s) ago');
check('toFriendly 中文', Timer::toFriendly(time() - 120), '2分钟前');
check('toFriendly 英文', Timer::toFriendly(time() - 120, 'en'), '2 minute(s) ago');
check('time_ago 助手函数', time_ago($base - 60, $base), '1分钟前');
check('time_format 助手函数', time_format('Y-m-d', '2026-05-06'), '2026-05-06');

// timeago：配置生效
Timer::setConfig(['timeago' => ['just_now_seconds' => 120]]);
check('配置 just_now_seconds', Timer::timeAgo($base - 100, $base), '刚刚');
Timer::resetConfig();

Timer::setConfig(['timeago' => ['max_unit' => 'day']]);
check('配置 max_unit=day', Timer::timeAgo($base - 10 * 86400, $base), '10天前');
Timer::resetConfig();

Timer::setConfig(['timeago' => ['max_unit' => 'month', 'date_format' => 'Y-m-d']]);
check('配置 date_format', Timer::timeAgo($base - 400 * 86400, $base), Timer::format('Y-m-d', $base - 400 * 86400));
Timer::resetConfig();

Timer::setConfig(['timeago' => ['allow_future' => false]]);
check('配置 allow_future=false', Timer::timeAgo($base + 3600, $base), '刚刚');
Timer::resetConfig();

Timer::setConfig(['timeago' => ['locales' => ['zh-cn' => ['templates' => ['minute' => '{value} 分钟以前']]]]]);
check('配置自定义文案', Timer::timeAgo($base - 120, $base), '2 分钟以前');
Timer::resetConfig();

Timer::setConfig(['timeago' => ['locale' => 'en']]);
check('配置默认语言', Timer::timeAgo($base - 120, $base), '2 minute(s) ago');
Timer::resetConfig();

Timer::setConfig(['timeago' => ['units' => ['day' => ['seconds' => 86400, 'limit' => 172800]]]]);
check('配置 units 阈值', Timer::timeAgo($base - 3 * 86400, $base), '1周前');
Timer::resetConfig();

// TimeRange
$day = TimeRange::day('2026-01-01 12:00:00');
check('TimeRange day start', $day['start'], strtotime('2026-01-01 00:00:00'));
check('TimeRange day end', $day['end'], strtotime('2026-01-02 00:00:00'));

$week = TimeRange::week('2026-01-01');
check('TimeRange week start', $week['start'], strtotime('2025-12-29 00:00:00'));
check('TimeRange week end', $week['end'], strtotime('2026-01-05 00:00:00'));

$month = TimeRange::month('2026-01-31', -1);
check('TimeRange 上月 start', $month['start'], strtotime('2025-12-01 00:00:00'));
check('TimeRange 上月 end', $month['end'], strtotime('2026-01-01 00:00:00'));

$quarter = TimeRange::quarter('2026-05-20');
check('TimeRange 季度 start', $quarter['start'], strtotime('2026-04-01 00:00:00'));
check('TimeRange 季度 end', $quarter['end'], strtotime('2026-07-01 00:00:00'));

check('contains 默认排除终点', TimeRange::contains(10, 0, 10), false);
check('contains 两端包含', TimeRange::contains(10, 0, 10, true, true), true);
check('contains 两端排除', TimeRange::contains(0, 0, 10, false, false), false);
checkThrows('contains 起点大于终点', static fn () => TimeRange::contains(5, 10, 0));

// ChinaHoliday
check('holiday 国庆', ChinaHoliday::isHoliday('2026-10-01'), true);
check('holiday 国庆不是工作日', ChinaHoliday::isWorkday('2026-10-01'), false);
check('holiday 调休上班', ChinaHoliday::isWorkday('2026-10-10'), true);
check('holiday 普通周一', ChinaHoliday::isWorkday('2026-10-12'), true);
check('holiday 普通周日', ChinaHoliday::isWorkday('2026-10-11'), false);

Timer::setConfig(['holiday' => ['enabled' => false]]);
check('配置 holiday.enabled=false', ChinaHoliday::isWorkday('2026-10-01'), true);
Timer::resetConfig();

Timer::setConfig(['holiday' => ['weekend_is_holiday' => false]]);
check('配置 weekend_is_holiday=false', ChinaHoliday::isWorkday('2026-10-11'), true);
Timer::resetConfig();

Timer::setConfig(['holiday' => ['holidays' => ['2028' => ['0103']]]]);
check('配置自定义节假日', ChinaHoliday::isHoliday('2028-01-03'), true);
Timer::resetConfig();

Timer::setConfig(['holiday' => ['workdays' => ['2028' => ['0101']]]]);
check('配置自定义调休', ChinaHoliday::isWorkday('2028-01-01'), true);
Timer::resetConfig();

echo sprintf("通过 %d 项，失败 %d 项\n", $passed, $failed);

exit($failed === 0 ? 0 : 1);