<?php

namespace Mumincacao\LaravelFileQueue\tests;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class TestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(readonly public string $message)
    {
        /// NOP
    }

    public function handle(): void
    {
        /// NOP
    }
}
