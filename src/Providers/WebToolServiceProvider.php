<?php

namespace Botble\WebTool\Providers;

use Illuminate\Support\ServiceProvider;

class WebToolServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->register(CommandServiceProvider::class);
    }
}
