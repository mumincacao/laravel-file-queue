<?php

declare(strict_types=1);

namespace Mumincacao\LaravelFileQueue;

use Illuminate\Support\ServiceProvider as SupportServiceProvider;

class ServiceProvider extends SupportServiceProvider
{
    public function boot(): void
    {
        $this->app['queue']->addConnector('file', fn () => new FileQueueConnector());
    }
}
