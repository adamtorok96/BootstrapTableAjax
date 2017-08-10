# Bootstrap Table Ajax for Laravel 5

### Links
<a href="http://bootstrap-table.wenzhixin.net.cn">Bootstrap (data) tables</a><br>
<a href="https://laravel.com">Laravel</a>

## Install
<code>composer require composer require adamtorok96/bootstrap-table-ajax</code>

Into <b>config/app.php</b> put this under providers:<br>
```
AdamTorok96\BootstrapTableAjax\BootstrapTableAjaxServiceProvider::class,
```

Into <b>config/app.php</b> put this under aliases:<br>
```
'AjaxResponse'  => AdamTorok96\BootstrapTableAjax\Facades\AjaxResponseFacade::class,
```

## Usage

### Example #1
```
use AdamTorok96\BootstrapTableAjax\AjaxResponse;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        return AjaxResponse::base(User::query(), $request)
            ->search([
                'name',
                'email'
            ])
            ->orderBy('name')
            ->get()
        ;
    }   
}
```

### Example #2
```
use AdamTorok96\BootstrapTableAjax\AjaxResponse;

class NewsController extends Controller
{
    public function index(Ajax $request)
    {
        return AjaxResponse::base(News::query(), $request)
            ->search([
                'title',
                'author.name'
            ])
            ->orderBy('title')
            ->with([
                'author'
            ])
            ->withCount([
                'comments'
            ])
            ->get()
        ;
    }
}
```