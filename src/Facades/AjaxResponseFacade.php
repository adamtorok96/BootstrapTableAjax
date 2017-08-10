<?php

namespace AdamTorok96\BootstrapTableAjax\Facades;


use Illuminate\Support\Facades\Facade;

class AjaxResponseFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return AjaxResponse::class;
    }
}