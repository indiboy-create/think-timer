# youvision/think-timer

ThinkPHP 8 时间日期助手组件，提供常用时间操作、可配置的友好时间（timeago）与国内节假日判断。

- 所有时间参数都支持任意格式字符串、时间戳、`DateTimeInterface` 对象
- 内置 `Timer`、`TimeRange`、`ChinaHoliday` 三个静态类
- timeago 支持“刚刚、N秒前、N分钟前、N小时前、N天前、N周前、N个月前、N年前”和未来时间
- timeago 的单位阈值、最大单位、日期回退、语言包、文案全部可配置
- 只支持 PHP >= 8.0 与 ThinkPHP 8

## 安装

```bash
composer require youvision/think-timer
```

包通过 `extra.think.services` 声明了服务，安装后执行一次服务发现：

```bash
php think service:discover
```

不执行服务发现也可以直接使用类与助手函数，因为配置读取会自动回退到包内默认值。

## 配置

将配置文件复制到 ThinkPHP 项目：

```bash
cp vendor/youvision/think-timer/config/timer.php config/timer.php
```

配置结构：

```php
return [
    'timeago' => [
        'locale' => 'zh-cn',          // 默认语言包
        'just_now_seconds' => 60,      // 小于该秒数显示“刚刚”
        'allow_future' => true,        // 是否显示“N小时后”
        'max_unit' => 'year',          // 最大显示单位
        'date_format' => null,         // 超过 max_unit 时改为日期格式
        'units' => [/* 单位阈值 */],
        'locales' => [/* 语言包与文案 */],
    ],
    'holiday' => [
        'enabled' => true,
        'holidays' => [],              // 追加法定节假日
        'workdays' => [],              // 追加调休上班日
        'weekend_is_holiday' => true,  // 周末是否休息
    ],
];
```

### timeago 配置项

| 配置项 | 类型 | 默认值 | 说明 |
| --- | --- | --- | --- |
| `locale` | string | `zh-cn` | 默认语言包，内置 `zh-cn`、`en` |
| `just_now_seconds` | int | `60` | 0 至该秒数减 1 显示“刚刚”；设为 0 时只有 0 秒显示“刚刚” |
| `allow_future` | bool | `true` | 未来时间显示“N分钟后”；`false` 时统一显示“刚刚” |
| `max_unit` | string | `year` | 允许显示的最大单位，超过后仍按该单位换算 |
| `date_format` | string\|null | `null` | 超过 `max_unit` 时改为指定日期格式，`null` 表示不启用 |
| `units` | array | 见配置 | 每个单位的 `seconds`（单位秒数）和 `limit`（切换上限） |
| `locales` | array | 见配置 | 语言包，包含 `just_now`、`templates`、`future_templates` |

单位默认阈值：

| 单位 | seconds | limit | 显示范围 |
| --- | --- | --- | --- |
| `second` | 1 | 60 | 1-59 秒 |
| `minute` | 60 | 3600 | 1-59 分钟 |
| `hour` | 3600 | 86400 | 1-23 小时 |
| `day` | 86400 | 604800 | 1-6 天 |
| `week` | 604800 | 2592000 | 1-4 周 |
| `month` | 2592000 | 31536000 | 1-12 个月 |
| `year` | 31536000 | null | 1 年以上 |

`templates` 中的 `{value}` 会被替换为具体数字。例如：

```php
'templates' => [
    'minute' => '{value}分钟前',
],
```

修改后，2 分钟前会输出“2分钟前”。

### 运行时覆盖

不想修改配置文件时，可以在运行时覆盖：

```php
use Youvision\ThinkTimer\Timer;

Timer::setConfig([
    'timeago' => [
        'just_now_seconds' => 120,
        'locale' => 'en',
    ],
]);

Timer::resetConfig(); // 恢复默认
```

## 使用

### 友好时间

```php
use Youvision\ThinkTimer\Timer;

Timer::timeAgo(time() - 120);
// 2分钟前

Timer::timeAgo(time() - 7200);
// 2小时前

Timer::timeAgo('2026-01-01 12:00:00', '2026-01-01 10:00:00');
// 2小时后（自定义基准时间，方便测试）

Timer::timeAgo(time() - 7200, null, ['locale' => 'en']);
// 2 hour(s) ago

Timer::toFriendly(time() - 7200);
// 2小时前
```

助手函数：

```php
time_ago(time() - 7200);
// 2小时前

time_format('Y-m-d', time());
// 当前日期
```

### 常用时间方法

```php
use Youvision\ThinkTimer\Timer;

Timer::now();                         // 当前时间 Y-m-d H:i:s
Timer::toTimestamp('2026-01-01');     // 任意格式转时间戳
Timer::format('Y-m-d', time());       // 任意格式转指定格式
Timer::isToday('2026-11-07 12:00:00');// 是否为今天
Timer::diffDays('2026-01-01', '2026-01-11'); // 10
Timer::beforeDay(15);
Timer::afterMonth(5);
Timer::getQuarter('2026-05-01');      // 2
Timer::isLeapYear('2024-01-01');      // true
Timer::daysInMonth('2024-02-01');     // 29
Timer::getMilliTimestamp();           // 毫秒时间戳
Timer::timezoneFormat('Asia/Shanghai', 'UTC', '2026-01-01 00:00:00');
// 2026-01-01 08:00:00
```

### 时间范围

```php
use Youvision\ThinkTimer\TimeRange;

TimeRange::day();                  // 今天 [00:00, 次日 00:00)
TimeRange::day(null, -1);          // 昨天
TimeRange::week();                 // 本周（周一为起点）
TimeRange::month();                // 本月
TimeRange::month(null, -1);        // 上月
TimeRange::quarter('2026-05-20');  // 本季度
TimeRange::year();                 // 今年

TimeRange::contains('2026-10-27', '2026-10-01', '2026-11-01'); // true
TimeRange::contains(10, 0, 10);    // false，默认不包含终点
TimeRange::contains(10, 0, 10, true, true); // true，两端都包含
```

所有范围返回 `['start' => int, 'end' => int]`，包含 `start`、不包含 `end`。

### 中国节假日

```php
use Youvision\ThinkTimer\ChinaHoliday;

ChinaHoliday::isWorkday('2026-10-01'); // false
ChinaHoliday::isHoliday('2026-10-01'); // true
ChinaHoliday::isWorkday('2026-10-10'); // true，调休上班
```

内置 2020-2027 年数据。后续年份可在 `config/timer.php` 中追加：

```php
'holiday' => [
    'holidays' => ['2028' => ['0101', '0102']],
    'workdays' => ['2028' => ['0204']],
],
```

## API 一览

### Timer

| 分类 | 方法 |
| --- | --- |
| 配置 | `config()`、`setConfig()`、`resetConfig()` |
| 转换 | `isTimestamp()`、`toTimestamp()`、`format()`、`modifyTimestamp()`、`now()` |
| 严格解析 | `parseExact()`、`isValidDate()` |
| 友好时间 | `timeAgo()`、`toFriendly()` |
| 秒数 | `secondUntilMidnight()`、`secondEndToday()`、`secondMinute()`、`secondHour()`、`secondDay()`、`secondWeek()` |
| 判断 | `isToday()`、`isYesterday()`、`isTomorrow()`、`isThisWeek()`、`isThisMonth()`、`isThisYear()`、`isWeekday()`、`isWeekend()`、`isLeapYear()` |
| 差值 | `diffSeconds()`、`diffMinutes()`、`diffHours()`、`diffDays()`、`diffWeeks()`、`diffMonths()`、`diffYears()` |
| 前后时间 | `beforeMinute()`、`afterMinute()`、`beforeHour()`、`afterHour()`、`beforeDay()`、`afterDay()`、`beforeWeek()`、`afterWeek()`、`beforeMonth()`、`afterMonth()`、`beforeYear()`、`afterYear()` |
| 高精度 | `getTimestamp()`、`getMilliTimestamp()`、`getMicroTimestamp()`、`getNanoTimestamp()` |
| 日期部分 | `getYear()`、`getQuarter()`、`getMonth()`、`getWeek()`、`getDay()`、`getHour()`、`getMinute()`、`getSecond()`、`getWeekDay()`、`daysInYear()`、`daysInMonth()` |
| 时区与比较 | `timezoneFormat()`、`compare()` |

### TimeRange

`period()`、`day()`、`week()`、`month()`、`quarter()`、`year()`、`contains()`

### ChinaHoliday

`isWorkday()`、`isHoliday()`


## 测试

```bash
php tests/run.php
```

或安装依赖后：

```bash
composer install
vendor/bin/phpunit
php tests/service-check.php
```

`tests/service-check.php` 用于验证 ThinkPHP 8 服务注册与配置合并。

## License

MIT