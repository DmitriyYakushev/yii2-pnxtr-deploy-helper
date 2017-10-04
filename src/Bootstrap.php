<?php

namespace yii\pinxter\deploy\helper;

use yii\base\BootstrapInterface;
use yii\console\Application;

class Bootstrap implements BootstrapInterface
{
    /**
     * Bootstrap method to be called during application bootstrap stage.
     * @param Application $app the application currently running
     */
    public function bootstrap($app)
    {
        if (!$app instanceof Application) {
            return;
        }

        $app->on(Application::EVENT_BEFORE_REQUEST, function () use ($app) {
            $app->controllerMap['init'] = __NAMESPACE__ . '\console\InitController';
        });
    }
}