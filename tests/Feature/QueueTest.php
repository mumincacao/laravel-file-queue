<?php

namespace Mumincacao\LaravelFileQueue\tests\Feature;

use Carbon\Carbon;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Arr;
use Mumincacao\LaravelFileQueue\FileJob;
use Mumincacao\LaravelFileQueue\FileQueue;
use Mumincacao\LaravelFileQueue\tests\TestJob;

class QueueTest extends TestCase
{
    public function createApplication()
    {
        $app = new Application(dirname(__DIR__));
        $app->singleton(HttpKernelContract::class, HttpKernel::class);
        $app->singleton(ConsoleKernelContract::class, ConsoleKernel::class);
        $app->singleton(ExceptionHandler::class, Handler::class);
        $app->make(ConsoleKernel::class)->bootstrap();
        return $app;
    }

    private function getFileQueue(): FileQueue
    {
        return app('queue')->connection('file');
    }

    private function getDispatchedJob(?Job $job): ?TestJob
    {
        if (!$job instanceof FileJob) {
            return null;
        }
        $job->fire();
        return unserialize(Arr::get($job->payload(), 'data.command'));
    }

    public function testFileQueue(): void
    {
        $queue = $this->getFileQueue();
        $queue->clear('default');
        $queue->clear('sub');
        $this->assertEquals($queue->size(), 0, 'Initial (default)');
        $this->assertEquals($queue->size('sub'), 0, 'Initial (sub)');

        TestJob::dispatch('Test Job');
        $this->assertEquals($queue->size(), 1, 'Pushed (default)');
        $this->assertEquals($queue->size('sub'), 0, 'Pushed (sub)');

        $defaultJob = $this->getDispatchedJob($queue->pop());
        $this->assertEquals($queue->size(), 0, 'Poped (default)');
        $this->assertEquals($queue->size('sub'), 0, 'Poped (sub)');
        $this->assertEquals($defaultJob->message, 'Test Job', 'Job payload');

        TestJob::dispatch('Sub Job')->onQueue('sub');
        $this->assertEquals($queue->size(), 0, 'Pushed (default)');
        $this->assertEquals($queue->size('sub'), 1, 'Pushed (sub)');

        $subJob = $this->getDispatchedJob($queue->pop('sub'));
        $this->assertEquals($queue->size(), 0, 'Pushed (default)');
        $this->assertEquals($queue->size('sub'), 0, 'Pushed (sub)');
        $this->assertEquals($subJob->message, 'Sub Job', 'Job payload');

        $this->assertNull($queue->pop(), 'Empty pop (default)');
        $this->assertNull($queue->pop('sub'), 'Empty pop (sub)');
    }

    public function testDelay(): void
    {
        $queue = $this->getFileQueue();
        $queue->clear('default');
        $this->assertEquals($queue->size(), 0, 'Initial');

        Carbon::setTestNow('2023-01-01 00:00:00');
        TestJob::dispatch('Tomorrow')->delay(60 * 60 * 24);
        TestJob::dispatch('Next hour')->delay(60 * 60);
        $this->assertEquals($queue->size(), 2, 'Pushed');

        $this->assertNull($queue->pop());
        $this->assertEquals($queue->size(), 2, 'Poped now');

        Carbon::setTestNow('2023-01-01 01:00:00');
        $hourJob = $this->getDispatchedJob($queue->pop());
        $this->assertEquals($queue->size(), 1, 'Poped hour');
        $this->assertEquals($hourJob->message, 'Next hour');
        $this->assertNull($queue->pop());
        $this->assertEquals($queue->size(), 1, 'Poped hour again');

        Carbon::setTestNow('2023-01-02 00:00:00');
        $dayJob = $this->getDispatchedJob($queue->pop());
        $this->assertEquals($queue->size(), 0, 'Poped day');
        $this->assertEquals($dayJob->message, 'Tomorrow');
        $this->assertNull($queue->pop());
        $this->assertEquals($queue->size(), 0, 'Poped day again');
    }

    public function testDeleteMissingJob(): void
    {
        $queue = $this->getFileQueue();
        $queue->clear('default');
        $queue->delete('default', 'dummy');
        $this->assertEquals($queue->size('default'), 0);
    }
}
