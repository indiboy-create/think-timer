<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| think-timer 配置文件
|--------------------------------------------------------------------------
|
| 将本文件复制到 ThinkPHP 项目的 config/timer.php 后即可按需修改。
| 未复制时组件会自动使用本文件的默认值，不需要任何额外配置。
|
*/

return [
    /*
    |--------------------------------------------------------------------------
    | 友好时间（timeago）
    |--------------------------------------------------------------------------
    |
    | 用于把时间戳转换成“刚刚、5分钟前、3小时前、2天前、2周前、3个月前”等
    | 更适合阅读的文案。所有文案、切换阈值、语言包都可以在这里调整。
    |
    */
    'timeago' => [
        /*
         * 默认语言包标识。
         * 取值必须是下面 locales 中存在的键。
         * 内置：zh-cn（简体中文）、en（英文）。
         */
        'locale' => 'zh-cn',

        /*
         * 小于该秒数时显示“刚刚”。
         * 例如设置为 60，则 0-59 秒显示“刚刚”；设置为 0，则只有 0 秒显示“刚刚”。
         */
        'just_now_seconds' => 60,

        /*
         * 是否允许显示未来的时间。
         * true ：未来时间使用 future_templates 中的模板，例如“2小时后”。
         * false：未来时间统一显示“刚刚”。
         */
        'allow_future' => true,

        /*
         * 允许显示的最大单位，必须是下面 units 中存在的键。
         * 例如设置为 day，则超过 7 天的数据仍然显示为“15天前”，不会切换成周/月/年。
         * 设置为 year 表示允许一直显示到“N年前”。
         */
        'max_unit' => 'year',

        /*
         * 超过 max_unit 后是否改为显示具体日期。
         * null 或空字符串表示不启用，继续按 max_unit 换算。
         * 例如：将 max_unit 设置为 'month'，date_format 设置为 'Y-m-d'，
         * 则超过 1 年的数据会直接显示为 2024-01-01。
         */
        'date_format' => null,

        /*
         * 单位定义，执行顺序为数组顺序。
         * 每个单位包含：
         *   seconds：该单位包含的秒数，用于计算“N”的值。
         *   limit  ：达到该秒数后切换到下一个单位；null 表示不再切换。
         *
         * 注意：单位键必须与语言包 templates / future_templates 中的键一致。
         * 如果删除了某个单位，同时需要删除语言包中对应模板。
         */
        'units' => [
            'second' => ['seconds' => 1, 'limit' => 60],
            'minute' => ['seconds' => 60, 'limit' => 3600],
            'hour' => ['seconds' => 3600, 'limit' => 86400],
            'day' => ['seconds' => 86400, 'limit' => 604800],
            'week' => ['seconds' => 604800, 'limit' => 2592000],
            'month' => ['seconds' => 2592000, 'limit' => 31536000],
            'year' => ['seconds' => 31536000, 'limit' => null],
        ],

        /*
         * 语言包。
         * just_now        ：小于 just_now_seconds 时显示的文案。
         * templates       ：过去时间模板，{value} 会被替换成具体数字。
         * future_templates：未来时间模板，{value} 会被替换成具体数字。
         *
         * 可以自行增加语言，例如 ja、zh-tw，只需保证键名与 units 一致。
         */
        'locales' => [
            'zh-cn' => [
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
            ],
            'en' => [
                'just_now' => 'just now',
                'templates' => [
                    'second' => '{value} second(s) ago',
                    'minute' => '{value} minute(s) ago',
                    'hour' => '{value} hour(s) ago',
                    'day' => '{value} day(s) ago',
                    'week' => '{value} week(s) ago',
                    'month' => '{value} month(s) ago',
                    'year' => '{value} year(s) ago',
                ],
                'future_templates' => [
                    'second' => 'in {value} second(s)',
                    'minute' => 'in {value} minute(s)',
                    'hour' => 'in {value} hour(s)',
                    'day' => 'in {value} day(s)',
                    'week' => 'in {value} week(s)',
                    'month' => 'in {value} month(s)',
                    'year' => 'in {value} year(s)',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 中国节假日
    |--------------------------------------------------------------------------
    |
    | 用于 ChinaHoliday::isWorkday() / isHoliday() 判断国内工作日与节假日。
    | 内置 2020-2027 年数据，可通过 holidays / workdays 追加或覆盖。
    |
    */
    'holiday' => [
        /*
         * 是否启用节假日数据。
         * true ：优先使用 holidays / workdays 数据判断。
         * false：忽略所有节假日数据，仅按周末判断。
         */
        'enabled' => true,

        /*
         * 追加“工作日中的休息日”（法定节假日）。
         * 格式：年份 => ['月日', '月日']，月日必须是两位，例如 ['0101', '1001']。
         * 同一年份会与内置数据合并，自定义数据会追加到内置数据之后。
         */
        'holidays' => [],

        /*
         * 追加“休息日中的工作日”（调休上班日）。
         * 格式与 holidays 相同，例如 ['2028' => ['0204', '0218']]。
         */
        'workdays' => [],

        /*
         * 周六、周日是否算作休息日。
         * true ：周末默认休息，只有 workdays 中配置的调休日才上班。
         * false：周末也视为工作日，只有 holidays 中配置的日期才休息。
         */
        'weekend_is_holiday' => true,
    ],
];