<?php

declare(strict_types=1);

namespace Youvision\ThinkTimer;

use think\Service;

/**
 * ThinkPHP 8 服务注册。
 *
 * composer.json 已通过 extra.think.services 声明本服务，
 * 安装后执行 php think service:discover 即可自动发现。
 */
class TimerService extends Service
{
    /**
     * 将组件默认配置合并到 ThinkPHP 的 timer 配置中。
     */
    public function register(): void
    {
        $defaults = Config::defaults();
        $current = $this->app->config->get('timer', []);

        if (!is_array($current)) {
            $current = [];
        }

        $this->app->config->set(Config::merge($defaults, $current), 'timer');
    }
}