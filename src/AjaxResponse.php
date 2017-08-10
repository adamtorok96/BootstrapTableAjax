<?php

namespace AdamTorok96\BootstrapTableAjax;


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
    protected $withCount;

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
     * @param array $relations
     * @return $this
     */
    public function with(array $relations)
    {
        $this->with = $relations;

        return $this;
    }

    /**
     * @param array $relations
     * @return $this
     */
    public function withCount(array $relations)
    {
        $this->withCount = $relations;

        return $this;
    }

    /**
     * @param string $column
     * @param string $order
     * @return $this
     */
    public function orderBy(string $column, string $order = 'ASC')
    {
        if( $this->orders === null )
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
        if( $this->orders === null )
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
        if( $this->orders === null )
            $this->orders = [];

        array_push($this->orders, [
            'column'    => $column,
            'order'     => 'DESC'
        ]);

        return $this;
    }

    /**
     * @param array $columns
     * @return $this
     */
    public function makeVisible(array $columns)
    {
        $this->makeVisible = $columns;

        return $this;
    }

    /**
     * @param array $columns
     * @return $this
     */
    public function makeHidden(array $columns)
    {
        $this->makeHidden = $columns;

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
            ->when($this->request && $this->search, function (Builder $query) {
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

        if( $this->makeVisible !== null )
            $rows->makeVisible($this->makeVisible);

        if( $this->makeHidden !== null )
            $rows->makeHidden($this->makeHidden);

        if( $this->each !== null )
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