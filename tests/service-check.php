<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

date_default_timezone_set('Asia/Shanghai');

use Youvision\ThinkTimer\TimerService;

$app = new think\App();
$app->config->set([
    'timeago' => [
        'just_now_seconds' => 120,
    ],
], 'timer');

$service = new TimerService($app);
$service->register();

$config = $app->config->get('timer');

$checks = [
    '默认配置已合并' => isset($config['timeago']['locale']) && $config['timeago']['locale'] === 'zh-cn',
    '用户配置优先级' => (int) $config['timeago']['just_now_seconds'] === 120,
    '语言包完整' => isset($config['timeago']['locales']['en']['templates']['minute']),
    '节假日配置存在' => isset($config['holiday']['weekend_is_holiday']),
];

$failed = 0;

foreach ($checks as $name => $result) {
    if (!$result) {
        $failed++;
        fwrite(STDERR, "[FAIL] {$name}\n");
    }
}

echo $failed === 0 ? "ThinkPHP Service 集成验证通过\n" : "ThinkPHP Service 集成验证失败\n";

exit($failed === 0 ? 0 : 1);