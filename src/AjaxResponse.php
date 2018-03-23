<?php

namespace AdamTorok96\BootstrapTableAjax;


use Exception;
use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class AjaxResponse
{
    /**
     * @var $base Builder
     */
    protected $base = null;

    /**
     * @var $request Request
     */
    protected $request = null;

    /**
     * @var $search array
     */
    protected $search = null;

    /**
     * @var $beforeCount callable
     */
    protected $beforeCount = null;

    /**
     * @var $afterCount callable
     */
    protected $afterCount = null;

    /**
     * @var $with array
     */
    protected $with = null;

    /**
     * @var $withCount array
     */
    protected $withCount = null;

    /**
     * @var $makeVisible array
     */
    protected $makeVisible = null;

    /**
     * @var $makeHidden array
     */
    protected $makeHidden = null;

    /**
     * @var $each callable
     */
    protected $each = null;

    /**
     * @var $orders array
     */
    protected $orders = null;

    /**
     * @var $whens array
     */
    protected $whens = [];

    /**
     * @param Builder $query
     * @param Request|null $request
     * @return mixed
     */
    public static function base(Builder $query, Request $request = null)
    {
        return (new AjaxResponse())->setBase($query)->request($request);
    }

    /**
     * @param Builder $query
     * @return $this
     */
    public function setBase(Builder $query) {
        $this->base = $query;

        return $this;
    }

    /**
     * @param Request $request
     * @return $this
     */
    public function request(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @param array $columns
     * @return $this
     */
    public function search(array $columns)
    {
        $this->search = $columns;

        return $this;
    }

    /**
     * @param callable $callable
     * @return $this
     */
    public function beforeCount(callable $callable)
    {
        $this->beforeCount = $callable;

        return $this;
    }

    /**
     * @param callable $callable
     * @return $this
     */
    public function afterCount(callable $callable)
    {
        $this->afterCount = $callable;

        return $this;
    }

    /**
     * @param string|array $relation
     * @return $this
     * @throws Exception
     */
    public function with($relation)
    {
        if( is_null($this->with) )
            $this->with = [];

        if( is_string($relation) )
            array_push($this->with, $relation);
        else if( is_array($relation) )
            $this->with = array_merge($this->with, $relation);
        else
            throw new Exception('Unknown type of relation!');

        return $this;
    }

    /**
     * @param string|array $relation
     * @return $this
     * @throws Exception
     */
    public function withCount($relation)
    {
        if( is_null($this->withCount) )
            $this->withCount = [];

        if( is_string($relation) )
            array_push($this->withCount, $relation);
        else if( is_array($relation) )
            $this->withCount = array_merge($this->withCount, $relation);
        else
            throw new Exception('Unknown type of relation!');

        return $this;
    }

    /**
     * @param string $column
     * @param string $order
     * @return $this
     */
    public function orderBy(string $column, string $order = 'ASC')
    {
        if( is_null($this->orders) )
            $this->orders = [];

        array_push($this->orders, [
            'column'    => $column,
            'order'     => $order
        ]);

        return $this;
    }

    /**
     * @param string $column
     * @return $this
     */
    public function oldest(string $column = 'created_at')
    {
        if( is_null($this->orders) )
            $this->orders = [];

        array_push($this->orders, [
            'column'    => $column,
            'order'     => 'ASC'
        ]);

        return $this;
    }

    /**
     * @param string $column
     * @return $this
     */
    public function latest(string $column = 'created_at')
    {
        if( is_null($this->orders) )
            $this->orders = [];

        array_push($this->orders, [
            'column'    => $column,
            'order'     => 'DESC'
        ]);

        return $this;
    }

    public function when(bool $when, callable $function)
    {
        array_push($this->whens, [
            'when'      => $when,
            'function'  => $function
        ]);

        return $this;
    }

    /**
     * @param string|array $column
     * @return $this
     * @throws Exception
     */
    public function makeVisible($column)
    {
        if( is_null($this->makeVisible) )
            $this->makeVisible = [];

        if( is_string($column) )
            array_push($this->makeVisible, $column);
        else if( is_array($column) )
            $this->makeVisible = array_merge($this->makeVisible, $column);
        else
            throw new Exception('Unknown type of column!');

        return $this;
    }

    /**
     * @param string|array $column
     * @return $this
     * @throws Exception
     */
    public function makeHidden($column)
    {
        if( is_null($this->makeHidden) )
            $this->makeHidden = [];

        if( is_string($column) )
            array_push($this->makeHidden, $column);
        else if( is_array($column) )
            $this->makeHidden = array_merge($this->makeHidden, $column);
        else
            throw new Exception('Unknown type of column!');

        return $this;
    }

    /**
     * @param callable $callable
     * @return $this
     */
    public function each(callable $callable)
    {
        $this->each = $callable;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getQueryBeforeCount()
    {
        return $this
            ->base
            ->when($this->request && empty($this->search) === false, function (Builder $query) {
                return $query->when($this->request->has('search'), function (Builder $query) {
                    return $query->where(function (Builder $query) {
                        foreach ($this->search as $column) {
                            $this->addSearch($query, $this->request, $column);
                        }
                    });
                });
            })
            ->when($this->beforeCount, $this->beforeCount)
        ;
    }

    /**
     * @param Builder $query
     * @return mixed
     */
    public function getQueryAfterCount(Builder $query)
    {
        return $query
            ->when($this->afterCount, $this->afterCount)
            ->when($this->request, function (Builder $query) {
                return $query
                    ->when($this->request->has('offset'), function (Builder $query) {
                        return $query->skip($this->request->offset);
                    })
                    ->when($this->request->has('limit'), function (Builder $query) {
                        return $query->take($this->request->limit);
                    })
                    ;
            })
            ->when($this->orders, function (Builder $query) {
                foreach ($this->orders as $order) {
                    $query->orderBy($order['column'], $order['order']);
                }

                return $query;
            })
            ->when($this->with, function (Builder $query) {
                return $query->with($this->with);
            })
            ->when($this->withCount, function (Builder $query) {
                return $query->withCount($this->withCount);
            })
        ;
    }

    public function get()
    {
        /**
         * @var $query Builder
         */
        $query = $this->getQueryBeforeCount();

        /**
         * @var $total int
         */
        $total = $query->count();

        /**
         * @var $rows Collection
         */
        $rows = $this->getQueryAfterCount($query)->get();

        if( isset($this->makeVisible) )
            $rows->makeVisible($this->makeVisible);

        if( isset($this->makeHidden) )
            $rows->makeHidden($this->makeHidden);

        if( isset($this->each) )
            $rows->each($this->each);

        return Container::getInstance()
            ->make(ResponseFactory::class)
            ->json([
                'total' => $total,
                'rows'  => $rows
            ])
        ;
    }

    /**
     * @param Builder $query
     * @param Request $request
     * @param $search
     * @return Builder|static
     */
    private function addSearch(Builder $query, Request $request, $search) {
        $exp = explode('.', $search);

        if( count($exp) == 1 )
            return $query->orWhere($exp[0], 'LIKE', '%' . $request->search .'%');

        $column = array_pop($exp);

        return $query->orWhereHas(
            implode('.', $exp),
            function (Builder $query) use($request, $column) {
                $query->where($column, 'LIKE', '%' . $request->search .'%');
            }
        );
    }
}