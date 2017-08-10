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
     * @param Builder $query
     * @return $this
     */
    public function base(Builder $query) {
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

    public function get()
    {
        /**
         * @var $query Builder
         */
        $query = $this
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

        $total = $query->count();

        /**
         * @var $rows Collection
         */
        $rows = $query
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
            ->when($this->with, function (Builder $query) {
                return $query->with($this->with);
            })
            ->when($this->withCount, function (Builder $query) {
                return $query->withCount($this->withCount);
            })
            ->get()
        ;

        if( $this->makeVisible !== null )
            $rows->makeVisible($this->makeVisible);

        if( $this->makeHidden !== null )
            $rows->makeHidden($this->makeHidden);

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