<?php

namespace AdamTorok96\BootstrapTableAjax\Facades;


use Illuminate\Support\Facades\Facade;

class AjaxResponse extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \AdamTorok96\BootstrapTableAjax\AjaxResponse::class;
    }
}