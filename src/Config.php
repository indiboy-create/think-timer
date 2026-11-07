<?php

declare(strict_types=1);

namespace Youvision\ThinkTimer;

use function function_exists;

/**
 * 组件配置读取与合并。
 *
 * 优先级：运行时覆盖（set） > ThinkPHP config('timer') > config/timer.php 默认值。
 */
final class Config
{
    /** @var array<string, mixed>|null */
    private static ?array $defaults = null;

    /** @var array<string, mixed> */
    private static array $overrides = [];

    /**
     * 获取组件默认配置。
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        if (self::$defaults === null) {
            $file = dirname(__DIR__) . '/config/timer.php';
            $config = is_file($file) ? require $file : [];
            self::$defaults = is_array($config) ? $config : [];
        }

        return self::$defaults;
    }

    /**
     * 获取合并后的完整配置。
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        $config = self::defaults();

        if (function_exists('config')) {
            $runtime = config('timer');
            if (is_array($runtime)) {
                $config = self::merge($config, $runtime);
            }
        }

        return self::merge($config, self::$overrides);
    }

    /**
     * 按点语法读取配置。
     *
     * @param string $key 例如 timeago.locale
     * @param mixed $default 配置不存在时返回的默认值
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $value = self::all();

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * 设置运行时配置，会与已有配置递归合并。
     *
     * @param array<string, mixed> $config
     */
    public static function set(array $config): void
    {
        self::$overrides = self::merge(self::$overrides, $config);
    }

    /**
     * 清空运行时配置。
     */
    public static function reset(): void
    {
        self::$overrides = [];
    }

    /**
     * 递归合并配置，$override 优先。
     *
     * @param array<string, mixed> $default
     * @param array<string, mixed> $override
     * @return array<string, mixed>
     */
    public static function merge(array $default, array $override): array
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($default[$key]) && is_array($default[$key])) {
                $default[$key] = self::merge($default[$key], $value);
            } else {
                $default[$key] = $value;
            }
        }

        return $default;
    }
}