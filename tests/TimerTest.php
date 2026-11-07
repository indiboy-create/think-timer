<?php

declare(strict_types=1);

namespace Youvision\ThinkTimer\Tests;

use PHPUnit\Framework\TestCase;
use Youvision\ThinkTimer\ChinaHoliday;
use Youvision\ThinkTimer\TimeRange;
use Youvision\ThinkTimer\Timer;

final class TimerTest extends TestCase
{
    protected function tearDown(): void
    {
        Timer::resetConfig();
    }

    public function testTimeAgoUnits(): void
    {
        $base = Timer::toTimestamp('2026-01-01 12:00:00');

        self::assertSame('刚刚', Timer::timeAgo($base - 30, $base));
        self::assertSame('2分钟前', Timer::timeAgo($base - 120, $base));
        self::assertSame('3小时前', Timer::timeAgo($base - 3 * 3600, $base));
        self::assertSame('4天前', Timer::timeAgo($base - 4 * 86400, $base));
        self::assertSame('2周前', Timer::timeAgo($base - 14 * 86400, $base));
        self::assertSame('3个月前', Timer::timeAgo($base - 90 * 86400, $base));
        self::assertSame('2年前', Timer::timeAgo($base - 730 * 86400, $base));
        self::assertSame('2小时后', Timer::timeAgo($base + 2 * 3600, $base));
    }

    public function testTimeAgoConfig(): void
    {
        $base = Timer::toTimestamp('2026-01-01 12:00:00');

        Timer::setConfig(['timeago' => ['just_now_seconds' => 120]]);
        self::assertSame('刚刚', Timer::timeAgo($base - 100, $base));

        Timer::setConfig(['timeago' => ['max_unit' => 'day']]);
        self::assertSame('10天前', Timer::timeAgo($base - 10 * 86400, $base));

        self::assertSame('2 minute(s) ago', Timer::timeAgo($base - 120, $base, ['locale' => 'en']));
    }

    public function testTimeRange(): void
    {
        $month = TimeRange::month('2026-01-31', -1);

        self::assertSame(strtotime('2025-12-01 00:00:00'), $month['start']);
        self::assertSame(strtotime('2026-01-01 00:00:00'), $month['end']);
        self::assertTrue(TimeRange::contains(10, 0, 10, true, true));
        self::assertFalse(TimeRange::contains(10, 0, 10));
    }

    public function testChinaHoliday(): void
    {
        self::assertTrue(ChinaHoliday::isHoliday('2026-10-01'));
        self::assertTrue(ChinaHoliday::isWorkday('2026-10-10'));
        self::assertFalse(ChinaHoliday::isWorkday('2026-10-11'));
    }

    public function testStrictDateParsing(): void
    {
        self::assertFalse(Timer::isValidDate('2026-02-31'));
        self::assertTrue(Timer::isValidDate('2026-02-28'));
        self::assertSame(strtotime('2026-04-03 00:00:00'), Timer::parseExact('03/04/2026', 'd/m/Y'));
    }
}