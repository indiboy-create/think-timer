<?php

declare(strict_types=1);

namespace Youvision\ThinkTimer;

/**
 * 可配置的友好时间格式化器。
 *
 * 支持“刚刚、N秒前、N分钟前、N小时前、N天前、N周前、N个月前、N年前”，
 * 支持未来时间，支持多语言与运行时覆盖配置。
 */
final class TimeAgo
{
    /**
     * 格式化友好时间。
     *
     * @param int|string|\DateTimeInterface $datetime 要格式化的时间
     * @param int|string|null $reference 基准时间，null 表示当前时间
     * @param array<string, mixed> $options 本次调用的配置覆盖，例如 ['locale' => 'en']
     */
    public static function format($datetime, $reference = null, array $options = []): string
    {
        $config = Config::merge(Config::get('timeago', []), $options);
        $locales = is_array($config['locales'] ?? null) ? $config['locales'] : [];
        $locale = self::resolveLocale($config, isset($options['locale']) ? (string) $options['locale'] : null);
        $messages = isset($locales[$locale]) && is_array($locales[$locale])
            ? $locales[$locale]
            : self::fallbackMessages();

        $timestamp = Timer::toTimestamp($datetime);
        $now = $reference === null ? time() : Timer::toTimestamp($reference);
        $diff = $now - $timestamp;
        $abs = abs($diff);
        $justNow = max(0, (int) ($config['just_now_seconds'] ?? 60));

        if ($abs === 0 || $abs < $justNow) {
            return (string) ($messages['just_now'] ?? '刚刚');
        }

        $future = $diff < 0;
        if ($future && empty($config['allow_future'])) {
            return (string) ($messages['just_now'] ?? '刚刚');
        }

        [$unit, $unitIndex] = self::resolveUnit($config, $abs);
        if ($unit === null) {
            return (string) ($messages['just_now'] ?? '刚刚');
        }

        $units = is_array($config['units'] ?? null) ? $config['units'] : [];
        $order = array_keys($units);
        $maxUnit = isset($config['max_unit']) ? (string) $config['max_unit'] : null;
        $maxIndex = $maxUnit !== null ? array_search($maxUnit, $order, true) : false;

        if ($maxIndex !== false && $unitIndex > $maxIndex) {
            $dateFormat = $config['date_format'] ?? null;
            if (is_string($dateFormat) && $dateFormat !== '') {
                return Timer::format($dateFormat, $timestamp);
            }
            $unit = $maxUnit;
        }

        $unitSeconds = isset($units[$unit]['seconds']) ? (int) $units[$unit]['seconds'] : 1;
        $value = max(1, (int) floor($abs / max(1, $unitSeconds)));

        $pastTemplates = is_array($messages['templates'] ?? null) ? $messages['templates'] : [];
        $futureTemplates = is_array($messages['future_templates'] ?? null)
            ? $messages['future_templates']
            : $pastTemplates;
        $templates = $future ? $futureTemplates : $pastTemplates;

        $template = $templates[$unit] ?? ($pastTemplates[$unit] ?? '{value} ' . $unit);

        return str_replace('{value}', (string) $value, (string) $template);
    }

    /**
     * 归一化语言标识，兼容 zh / en 简写。
     */
    public static function normalizeLocale(string $locale): string
    {
        $locale = strtolower(str_replace('_', '-', trim($locale)));

        if ($locale === '') {
            return 'zh-cn';
        }

        $aliases = [
            'zh' => 'zh-cn',
            'cn' => 'zh-cn',
            'zh-hans' => 'zh-cn',
            'zh-chs' => 'zh-cn',
            'zh-cn' => 'zh-cn',
            'en-us' => 'en',
            'en-gb' => 'en',
            'en' => 'en',
        ];

        return $aliases[$locale] ?? $locale;
    }

    /**
     * @param array<string, mixed> $config
     * @return array{0: string|null, 1: int}
     */
    private static function resolveUnit(array $config, int $abs): array
    {
        $units = is_array($config['units'] ?? null) ? $config['units'] : [];
        $lastUnit = null;
        $lastIndex = 0;
        $index = 0;

        foreach ($units as $name => $unit) {
            $lastUnit = (string) $name;
            $lastIndex = $index;
            $limit = is_array($unit) ? ($unit['limit'] ?? null) : null;

            if ($limit === null || $abs < (int) $limit) {
                return [(string) $name, $index];
            }

            $index++;
        }

        return [$lastUnit, $lastIndex];
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function resolveLocale(array $config, ?string $requested): string
    {
        $locales = is_array($config['locales'] ?? null) ? $config['locales'] : [];
        $default = self::normalizeLocale((string) ($config['locale'] ?? 'zh-cn'));
        $locale = $requested !== null ? self::normalizeLocale($requested) : $default;

        if (isset($locales[$locale])) {
            return $locale;
        }

        if (isset($locales[$default])) {
            return $default;
        }

        if ($locales !== []) {
            $keys = array_keys($locales);

            return (string) $keys[0];
        }

        return 'zh-cn';
    }

    /**
     * @return array<string, mixed>
     */
    private static function fallbackMessages(): array
    {
        $defaults = Config::defaults();
        $locales = $defaults['timeago']['locales'] ?? [];

        if (isset($locales['zh-cn']) && is_array($locales['zh-cn'])) {
            return $locales['zh-cn'];
        }

        return [
            'just_now' => '刚刚',
            'templates' => [
                'second' => '{value}秒前',
                'minute' => '{value}分钟前',
                'hour' => '{value}小时前',
                'day' => '{value}天前',
                'week' => '{value}周前',
                'month' => '{value}个月前',
                'year' => '{value}年前',
            ],
            'future_templates' => [
                'second' => '{value}秒后',
                'minute' => '{value}分钟后',
                'hour' => '{value}小时后',
                'day' => '{value}天后',
                'week' => '{value}周后',
                'month' => '{value}个月后',
                'year' => '{value}年后',
            ],
        ];
    }
}