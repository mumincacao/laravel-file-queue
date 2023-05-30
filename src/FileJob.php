<?php

declare(strict_types=1);

namespace Mumincacao\LaravelFileQueue;

use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as QueueJob;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Support\Arr;

class FileJob extends Job implements QueueJob
{
    /**
     * @var array<string,int|string|bool|array<string,string>|null>
     */
    protected array $data = [];

    public function __construct(
        Container $container,
        readonly protected FileQueue $fileQueue,
        protected string $rawPayload,
        string $queue
    ) {
        $this->container = $container;
        $this->queue = $queue;
        $this->data = $this->payload();
    }

    public function getRawBody(): string
    {
        return $this->rawPayload;
    }

    public function delete(): void
    {
        parent::delete();
        $this->fileQueue->delete($this->getQueue(), $this->getJobId());
    }

    public function release($delay = 0): void
    {
        parent::release($delay);
        $queue = $this->getQueue();
        $this->fileQueue->delete($queue, $this->getJobId());
        $data = Arr::get($this->data, 'data', '');
        $this->fileQueue->later($delay, $this, $data, $queue);
    }

    public function attempts(): int
    {
        return Arr::get($this->data, 'attempts', 0) + 1;
    }

    public function getJobId(): string
    {
        return Arr::get($this->data, 'uuid');
    }
}
