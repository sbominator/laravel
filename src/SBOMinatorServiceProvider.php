<?php

namespace SBOMinator\Laravel;

use Illuminate\Support\ServiceProvider;
use SBOMinator\Laravel\Console\SBOMinatorGenerateCommand;

class SBOMinatorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SBOMinatorGenerateCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        // You can register bindings here if needed
    }
}