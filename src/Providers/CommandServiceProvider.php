<?php

namespace Botble\WebTool\Providers;

use Botble\WebTool\Commands\UploadFileCommand;
use Botble\WebTool\Commands\ModuleDetailCommand;
use Illuminate\Support\ServiceProvider;

class CommandServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                UploadFileCommand::class,
                ModuleDetailCommand::class,
            ]);
        }
    }
}
