<?php

namespace AdamTorok96\BootstrapTableAjax;


use Illuminate\Support\ServiceProvider;

class BootstrapTableAjaxServiceProvider extends ServiceProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(AjaxResponseFacade::class, function () {
            return new AjaxResponseFacade();
        });
    }

    public function provides()
    {
        return [AjaxResponseFacade::class];
    }
}