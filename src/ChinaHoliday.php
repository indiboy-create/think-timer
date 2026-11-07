<?php

declare(strict_types=1);

namespace Youvision\ThinkTimer;

/**
 * 中国法定节假日判断。
 *
 * 内置 2020-2027 年数据，可通过 config/timer.php 的 holiday 配置追加。
 */
final class ChinaHoliday
{
    /** @var array<string, string[]> 工作日中的休息日（法定节假日） */
    private static array $holidays = [
        '2020' => ['0101', '0124', '0127', '0128', '0129', '0130', '0406', '0501', '0504', '0505', '0625', '0626', '1001', '1002', '1005', '1006', '1007', '1008'],
        '2021' => ['0101', '0211', '0212', '0215', '0216', '0217', '0405', '0503', '0504', '0505', '0614', '0920', '0921', '1001', '1004', '1005', '1006', '1007'],
        '2022' => ['0103', '0131', '0201', '0202', '0203', '0204', '0404', '0405', '0502', '0503', '0504', '0603', '0912', '1003', '1004', '1005', '1006', '1007'],
        '2023' => ['0102', '0123', '0124', '0125', '0126', '0127', '0405', '0501', '0502', '0503', '0622', '0623', '0929', '1002', '1003', '1004', '1005', '1006'],
        '2024' => ['0101', '0212', '0213', '0214', '0215', '0216', '0404', '0405', '0501', '0502', '0503', '0610', '0916', '0917', '1001', '1002', '1003', '1004', '1007'],
        '2025' => ['0101', '0128', '0129', '0130', '0131', '0203', '0204', '0404', '0501', '0502', '0505', '0602', '1001', '1002', '1003', '1006', '1007', '1008'],
        '2026' => ['0101', '0102', '0216', '0217', '0218', '0219', '0220', '0223', '0406', '0501', '0504', '0505', '0619', '0925', '1001', '1002', '1005', '1006', '1007'],
        '2027' => ['0101', '0205', '0208', '0209', '0210', '0211', '0212', '0405', '0503', '0504', '0505', '0609', '0915', '1001', '1004', '1005', '1006', '1007'],
    ];

    /** @var array<string, string[]> 休息日中的工作日（调休上班日） */
    private static array $workdays = [
        '2020' => ['0119', '0201', '0426', '0509', '0628', '0927', '1010'],
        '2021' => ['0207', '0220', '0425', '0508', '0918', '0926', '1009'],
        '2022' => ['0129', '0130', '0402', '0424', '0507', '1008', '1009'],
        '2023' => ['0128', '0129', '0423', '0506', '0625', '1007', '1008'],
        '2024' => ['0204', '0218', '0407', '0428', '0511', '0914', '0929', '1012'],
        '2025' => ['0126', '0208', '0427', '0928', '1011'],
        '2026' => ['0104', '0214', '0228', '0509', '0920', '1010'],
        '2027' => ['0131', '0214', '0508', '0926', '1009'],
    ];

    /**
     * 是否为国内的工作日。
     *
     * 判断顺序：调休上班日 > 法定节假日 > 周六周日 > 周一至周五。
     *
     * @param mixed $datetime 任意格式时间，默认为当前时间
     */
    public static function isWorkday($datetime = null): bool
    {
        $year = Timer::format('Y', $datetime);
        $monthDay = Timer::format('md', $datetime);
        $enabled = (bool) Timer::config('holiday.enabled', true);

        if ($enabled) {
            $holidays = self::mergeCustom(self::$holidays, 'holiday.holidays');
            $workdays = self::mergeCustom(self::$workdays, 'holiday.workdays');

            if (in_array($monthDay, $workdays[$year] ?? [], true)) {
                return true;
            }

            if (in_array($monthDay, $holidays[$year] ?? [], true)) {
                return false;
            }
        }

        if (Timer::isWeekend($datetime)) {
            return !(bool) Timer::config('holiday.weekend_is_holiday', true);
        }

        return true;
    }

    /**
     * 是否为国内的节假日（非工作日）。
     *
     * @param mixed $datetime 任意格式时间，默认为当前时间
     */
    public static function isHoliday($datetime = null): bool
    {
        return !self::isWorkday($datetime);
    }

    /**
     * 将配置中的自定义日期合并到内置数据。
     *
     * @param array<string, string[]> $base
     * @return array<string, string[]>
     */
    private static function mergeCustom(array $base, string $configKey): array
    {
        $custom = Timer::config($configKey, []);

        if (!is_array($custom)) {
            return $base;
        }

        foreach ($custom as $year => $dates) {
            $key = (string) $year;
            $existing = $base[$key] ?? [];
            $dates = is_array($dates) ? $dates : [$dates];
            $dates = array_map(static fn ($date): string => (string) $date, $dates);
            $base[$key] = array_values(array_unique(array_merge($existing, $dates)));
        }

        return $base;
    }
}