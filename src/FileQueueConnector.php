<?php

declare(strict_types=1);

namespace Mumincacao\LaravelFileQueue;

use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Support\Arr;

class FileQueueConnector implements ConnectorInterface
{
    /**
     * @param   array{'path': string, 'queue'?: string} $config
     */
    public function connect(array $config): Queue
    {
        return new FileQueue(
            Arr::get($config, 'path'),
            Arr::get($config, 'queue', 'default'),
            Arr::get($config, 'is_absolute', false),
            Arr::get($config, 'permission', 0777),
        );
    }
}
