<?php

declare(strict_types=1);

namespace Mumincacao\LaravelFileQueue;

use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ClearableQueue;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Queue\Queue as QueueQueue;
use Illuminate\Queue\Queue;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

class FileQueue extends Queue implements QueueQueue, ClearableQueue
{
    protected readonly string $path;

    public function __construct(
        string $path,
        readonly protected string $defaultQueue = 'default',
        bool $isAbsolute = false,
        readonly protected int $permission = 0777
    ) {
        $this->path = $isAbsolute ? $path : storage_path($path);
    }

    public function size($queue = null): int
    {
        return count($this->getEntries($queue));
    }

    public function push($job, $data = '', $queue = null): mixed
    {
        return $this->later(0, $job, $data, $queue);
    }

    public function pushRaw($payload, $queue = null, array $options = []): mixed
    {
        throw_if(count($options) > 0, RuntimeException::class);
        return $this->laterRaw(0, $payload, $queue);
    }

    public function later($delay, $job, $data = '', $queue = null): mixed
    {
        $queueName = $this->getQueueName($queue);
        $payload = $this->createPayload($job, $queueName, $data);
        return $this->laterRaw($delay, $payload, $queue);
    }

    public function laterRaw(DateTimeInterface|DateInterval|int $delay, string $payload, ?string $queue = null): mixed
    {
        $uuid = Arr::get(json_decode($payload, true), 'uuid');
        $queuePath = $this->getQueuePath($queue);
        $availableAt = (string) $this->availableAt($delay);
        $path = "{$queuePath}/{$availableAt}_{$uuid}_queue";
        if (is_dir(dirname($path)) === false) {
            mkdir(dirname($path), $this->permission, true);
        }
        file_put_contents($path, $payload);
        return $uuid;
    }

    public function pop($queue = null): ?Job
    {
        $queue = $this->getQueueName($queue);
        $entry = $this->getFirstEntry($queue);
        if ($entry === null) {
            return null;
        }
        [$time, $uuid] = explode('_', basename($entry));
        if ($time > $this->currentTime()) {
            return null;
        }
        throw_unless($payload = file_get_contents($entry));
        $this->delete($queue, $uuid);
        return new FileJob($this->container, $this, $payload, $queue);
    }

    public function delete(string $queue, string $uuid): void
    {
        $entry = $this->getFirstEntry($queue, $uuid);
        if (file_exists($entry)) {
            unlink($entry);
        }
    }

    public function clear($queue)
    {
        $num = 0;
        foreach ($this->getEntries($queue) as $entry) {
            unlink($entry);
            $num++;
        }
        return $num;
    }

    protected function getQueueName(?string $queue = null): string
    {
        return is_null($queue) ? $this->defaultQueue : $queue;
    }

    protected function getQueuePath(?string $queue = null): string
    {
        return "{$this->path}/{$this->getQueueName($queue)}";
    }

    /**
     * @return array<string>
     */
    protected function getEntries(?string $queue = null, string $uuid = '*'): array
    {
        $path = "{$this->getQueuePath($queue)}/*_{$uuid}_queue";
        $entries = glob($path, GLOB_MARK);
        throw_if($entries === false);
        return array_filter($entries, fn (string $value) => Str::endsWith($value, '_queue'));
    }

    protected function getFirstEntry(?string $queue = null, string $uuid = '*'): ?string
    {
        return Arr::first($this->getEntries($queue, $uuid));
    }
}
