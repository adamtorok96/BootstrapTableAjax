<?php

namespace AdamTorok96\BootstrapTableAjax;


use Illuminate\Support\ServiceProvider;

class BootstrapTableAjaxServiceProvider extends ServiceProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(AjaxResponse::class, function () {
            return new AjaxResponse();
        });
    }

    public function provides()
    {
        return [AjaxResponse::class];
    }
}