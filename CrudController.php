<?php

namespace App\Http\Controllers;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

/**
 * 1.创建Model类，写$table、$fillable、一对多关系hasMany和belongsTo
 * 2.创建控制器，继承这个增删改查类
 * 3.Route::group(['middleware' => 'api','prefix' => '组名'], function () {
 *     Route::post('/', '控制器名@store');
 *     Route::get('/', '控制器名@index');
 *     Route::get('/{id}', '控制器名@show');
 *     Route::match(['put', 'patch'] , '/{id}' , '控制器名@update');
 *     Route::delete('/{id}', '控制器名@destroy');
 *    });
 */
class CrudController extends Controller
{
    protected $Type = null;

    public function __construct($Type = null)
    {
        $this->Type = $Type;
    }

    /**
     * 根据条件获取一些数据
     * fields=id,name
     * s=[{"id":1},{"id":{"$gt":"1"},"createAt":{"$between":["2020-1-1","2020-5-1"]}}]
     * join=teams
     * sort=[{"id":"desc"},{"name":"asc"}]
     * limit=10
     * page=1 设置分页后offset失效
     * offset=10
     * $查询操作符
     * $eq (=, equal)
     * $ne (!=, not equal)
     * $gt (>, greater than)
     * $lt (<, lower that)
     * $gte (>=, greater than or equal)
     * $lte (<=, lower than or equal)
     * $starts (LIKE val%, starts with)
     * $ends (LIKE %val, ends with)
     * $cont (LIKE %val%, contains)
     * $excl (NOT LIKE %val%, not contains)
     * $in (IN, in range, accepts multiple values)
     * $notin (NOT IN, not in range, accepts multiple values)
     * $isnull (IS NULL, is NULL, doesn't accept value)
     * $notnull (IS NOT NULL, not NULL, doesn't accept value)
     * $between (BETWEEN, between, accepts two values)
     * @return \Illuminate\Http\Response
     */
    public function index(Request $req)
    {
        return $this->getPageResult($req->input(), $this->Type::query());
    }

    /**
     * 保存数据
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $req)
    {
        ($data = $this->Type::create($req->input()))->save();
        return $data;
    }

    /**
     * 获取一个数据
     * fields=id,name
     * join=teams
     * @return \Illuminate\Http\Response
     */
    public function show(Request $req, $id)
    {
        return $this->getOneResult($id, $req->input(), $this->Type::select());
    }


    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $req, $id)
    {
        $model = $this->Type::find($id);
        $model->fill($req->input())->save();
        return $model;
    }

    /**
     * 批量更改
     * @param Request $req
     * @query s:{} 查询条件
     * @query v:{} 要更改的列：值
     */
    public function patchUpdate(Request $req)
    {
        $input = $req->input();
        $query = $this->Type::query();

        $query->when(Arr::has($input, 's'), function (Builder $query) use ($input) {
            $this->buildSearch($query, json_decode($input['s'], true));
        });

        // \Illuminate\Support\Facades\DB::connection()->enableQueryLog();
        $data = $query->update(json_decode($input['v'], true));
        // dd(\Illuminate\Support\Facades\DB::getQueryLog());
        return $data;
    }

    /**
     * 根据ID删除一条记录，返回1为成功，0为失败
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        return $this->Type::destroy($id);
    }

    /**
     * 删除多个ID值，返回 删除了N条，0为失败
     *
     * @param [int] $ids
     * @return \Illuminate\Http\Response
     */
    public function destroyByIds($ids)
    {
        return $this->Type::destroy(explode(',',$ids));
    }

    /**
     * 根据条件删除
     *
     * @query [] $s
     * @return \Illuminate\Http\Response
     */
    public function destroyBy(Request $req)
    {
        $input = $req->input();
        $query = $this->Type::query();
        if(!(Arr::has($input, 's')) || count(json_decode($input['s'], true)) === 0){
            return 0;
        }
        $query->when(Arr::has($input, 's'), function (Builder $query) use ($input) {
            $this->buildSearch($query, json_decode($input['s'], true));
        });

        // \Illuminate\Support\Facades\DB::connection()->enableQueryLog();
        $data = $query->delete();
        // dd(\Illuminate\Support\Facades\DB::getQueryLog());

        return $data;
    }

    /**
     *
     * fields=id,name
     * s={"id":1,"id":{"$gt":"1"},"createAt":{"$between":["2020-1-1","2020-5-1"]},{"$or":{"tel":"aa"},{"name":"hehe"}}}}
     * sort={"id":"desc","name":"asc"}
     * limit=10
     * join=teams,joinb
     * join={"teams":{"s":{"id":1},"fields":"departmentId（外键在这里必须的）,id,name","sort":{"id":"desc"}},{"limit":1},"department"}
     * page=1 设置分页后offset失效
     * offset=10
     * sum=teams,teamb  合计联表总数
     * sum={"teams":{"s"{"id":1}}}
     * $查询操作符
     * $eq (=, equal)
     * $ne (!=, not equal)
     * $gt (>, greater than)
     * $lt (<, lower that)
     * $gte (>=, greater than or equal)
     * $lte (<=, lower than or equal)
     * $starts (LIKE val%, starts with)
     * $ends (LIKE %val, ends with)
     * $cont (LIKE %val%, contains)
     * $excl (NOT LIKE %val%, not contains)
     * $in (IN, in range, accepts multiple values)
     * $notin (NOT IN, not in range, accepts multiple values)
     * $isnull (IS NULL, is NULL, doesn't accept value)
     * $notnull (IS NOT NULL, not NULL, doesn't accept value)
     * $between (BETWEEN, between, accepts two values)
     * @param Array $input
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return array
     */
    protected function getPageResult($input, $query)
    {
        $query->when(Arr::has($input, 's'), function (Builder $query) use ($input) {
            $this->buildSearch($query, json_decode($input['s'], true));
        });

        // 没记录时不进行查询，直接返回空数组
        // \Illuminate\Support\Facades\DB::connection()->enableQueryLog();
        $total = $query->count();
        // dd(\Illuminate\Support\Facades\DB::getQueryLog());
        if ($total == 0) {
            return Arr::has($input, 'page') ? ['total' => $total, 'count' => 0, 'page' => 1, 'pageCount' => 0, 'data' => []] : [];
        }

        $query->when(Arr::has($input, 'join'), function (Builder $query) use ($input) {
            $this->buildJoin($query, $input['join']);
        });

        $query->when(Arr::has($input, 'sum'), function (Builder $query) use ($input) {
            $this->buildJoinCount($query, $input['sum']);
        });

        $this->buildSort($query, json_decode(Arr::get($input, 'sort', '{"id":"desc"}'), true));

        $pagesize = Arr::get($input, 'limit', $total);
        if (Arr::has($input, 'limit')) {
            $query->limit($input['limit']);
        }

        $page = Arr::get($input, 'page', 1);
        if (Arr::has($input, 'page')) {
            $query->offset(($page - 1) * $pagesize);
        } else if (Arr::has($input, 'offset')) {
            $query->offset($input['offset']);
        }
        // \Illuminate\Support\Facades\DB::connection()->enableQueryLog();
        $data = optional($query->get())->toArray();
        // dd(\Illuminate\Support\Facades\DB::getQueryLog());

        if (Arr::has($input, 'fields')) {
            $data = array_map(function ($item) use ($input) {
                return Arr::only($item, explode(',', $input['fields']));
            }, $data);
        }

        // 请求分页时返回页码信息，否则直接返回数据
        if (Arr::has($input, 'page')) {
            return [
                'total' => $total,
                'count' => count($data),
                'page' => $page,
                'pageCount' => ceil($total / $pagesize),
                'data' => $data
            ];
        } else {
            return $data;
        }
    }

    /**
     * 联表查询提供两种形式，一种只填表名，用逗号隔开
     * 另一种可以用json对象描述联表查的条件
     * $join = joinTableA,joinTableB;
     * $join = '{"joinTableA":{"fields":["sfsfsf","sfsfsf"],"s":{"tel":"hehe","$or":[{"tel":"hehe"},{"fjfj":"sfsfsf"}]}},"joinTableB"}';
     */
    private function buildJoin(&$query, $inputJoin)
    {
       if(!(Str::startsWith($inputJoin,'{'))){
            $query->with(explode(',', $inputJoin));
            return;
        }

        $join = json_decode($inputJoin, true);

        // 虽然传了json但是都是空值，这时取全部key做为with
        if(count($join) == count($join,1)){
            $query->with(array_keys($join));
            return;
        }

        $with = [];
        // 有子对象查询的处理
        foreach ($join as $key=>$val){
            // 混合数组中只有一个join的表名
            if(is_numeric($key)){
                $with[$val]=function(){ };
            }else{
                $with[$key] = function($joinQuery) use($val){
                    $joinQuery->when(Arr::has($val, 'fields'), function (Builder $joinQuery) use ($val) {
                        $joinQuery->select(explode(',', $val['fields']));
                    });
                    $joinQuery->when(Arr::has($val, 's'), function (Builder $joinQuery) use ($val) {
                        $this->buildSearch($joinQuery, $val['s']);
                    });
                    $joinQuery->when(Arr::has($val, 'sort'), function (Builder $joinQuery) use ($val) {
                        $this->buildSort($joinQuery, $val['sort']);
                    });
                    $joinQuery->when(Arr::has($val, 'limit'), function (Builder $joinQuery) use ($val) {
                        $joinQuery->limit($val['limit']);
                    });

                };
            }
        }
        $query->with($with);
//        $join = [];
//        foreach (explode(',', $input['join']) as $item) {
//            $join[$item] = function ($q) use ($input, $item) {
//                if (Arr::has($input, 's')) {
//                    $search = json_decode($input['s'], true);
//                    if (count($search) == 0) return;
//                    $search = Arr::pluck($search, $item);
//                    if (count($search) == 0) return;
//                    $search = array_filter($search);
//                    if (count($search) == 0) return;
//                    $search = head($search);
//                    if (count($search) == 0) return;
//                    foreach ($search as $searchitem) {
//                        $this->buildOP($q, $searchitem);
//                    }
//                }
//            };
//        }
//        $query->with($join);
    }

    /**
     * 联表查询提供两种形式，一种只填表名，用逗号隔开
     * 另一种可以用json对象描述联表查的条件
     * $join = joinTableA,joinTableB;
     * $join = '{"joinTableA":{"fields":["sfsfsf","sfsfsf"],"s":{"tel":"hehe","$or":[{"tel":"hehe"},{"fjfj":"sfsfsf"}]}},"joinTableB"}';
     */
    private function buildJoinCount(&$query, $inputJoinCount)
    {
        if(!(Str::startsWith($inputJoinCount,'{'))){
            $query->withCount(explode(',', $inputJoinCount));
            return;
        }
        $joinCount = json_decode($inputJoinCount, true);
        // 虽然传了json但是都是空值，这时取全部key做为with
        if(count($joinCount) == count($joinCount,1)){
            $query->withCount(array_keys($joinCount));
            return;
        }

        $withCount = [];
        // 有子对象查询的处理
        foreach ($joinCount as $key=>$val){
            // 混合数组中只有一个join的表名
            if(is_numeric($key)){
                $withCount[$val]=function(){ };
            }else{
                $withCount[$key] = function($joinCountQuery) use($val){
                    $joinCountQuery->when(Arr::has($val, 's'), function (Builder $joinQuery) use ($val) {
                        $this->buildSearch($joinQuery, $val['s']);
                    });
                };
            }
        }
        $query->withCount($withCount);
    }

    private function buildSearch(&$query, $s)
    {
        //$json = '{"$or":[{"tel":"hehe","b":"bb"},{"account":{"$cont":"haha"}},{"d":11}],"sex":{"$in":[1,2,3]},"disable":0,"age":{"$gt":22}}';
        //// (a=hehe or b=haha) and c>11 and (d==11)
        $where = ['and' => [], 'or' => []];
        foreach ($s as $key => $val) {
            ($key == '$or') ?
                (is_array($val) ?
                    $where['or'] = $val :
                    array_push($where['or'], $val)) :
                $where['and'][$key] = $val;
        }
        $this->buildOP($query, $where['and']);
        if(count($where['or']) > 0){
            $query->where(function($queryByOr) use($where){
                $this->buildOP($queryByOr, $where['or'], true);
            });
        }
    }

    private function buildOP(&$query, $item, $orWhere=false)
    {
        foreach ($item as $key => $value) {
            if (is_bool($value) || is_numeric($value) || is_string($value)) {
                $orWhere ? $query->orWhere($key, $value) : $query->where($key, $value);
            } else if (is_array($value)) {
                if (Str::startsWith(key($value), '$')) {
                    $op = key($value);
                    $val = $value[$op];
                    switch ($op) {
                        case '$eq':
                            $orWhere ? $query->orWhere($key, $val) : $query->where($key, $val);
                            break;
                        case '$ne':
                            $orWhere ? $query->orWhere($key, '!=', $val) : $query->where($key, '!=', $val);
                            break;
                        case '$gt':
                            $orWhere ? $query->orWhere($key, '>', $val) : $query->where($key, '>', $val);
                            break;
                        case '$lt':
                            $orWhere ? $query->orWhere($key, '<', $val) : $query->where($key, '<', $val);
                            break;
                        case '$gte':
                            $orWhere ? $query->orWhere($key, '>=', $val) : $query->where($key, '>=', $val);
                            break;
                        case '$lte':
                            $orWhere ? $query->orWhere($key, '<=', $val) : $query->where($key, '<=', $val);
                            break;
                        case '$starts':
                            $orWhere ? $query->orWhere($key, 'like', $val . '%') : $query->where($key, 'like', $val . '%');
                            break;
                        case '$ends':
                            $orWhere ? $query->orWhere($key, 'like', '%' . $val) : $query->where($key, 'like', '%' . $val);
                            break;
                        case '$cont':
                        case '$contL':
                            $orWhere ? $query->orWhere($key, 'like', '%' . $val . '%') : $query->where($key, 'like', '%' . $val . '%');
                            break;
                        case '$excl':
                            $orWhere ? $query->orWhere($key, 'not like', '%' . $val . '%') : $query->where($key, 'not like', '%' . $val . '%');
                            break;
                        case '$in':
                            $orWhere ? $query->orWhereIn($key, $val) : $query->whereIn($key, $val);
                            break;
                        case '$notin':
                            $orWhere ? $query->orWhereNotIn($key, $val) : $query->whereNotIn($key, $val);
                            break;
                        case '$isnull':
                            $orWhere ? $query->orWhereNull($key) : $query->whereNull($key);
                            break;
                        case '$notnull':
                            $orWhere ? $query->orWhereNotNull($key) : $query->whereNotNull($key);
                            break;
                        case '$between':
                            $orWhere ? $query->orWhereBetween($key, $val) : $query->whereBetween($key, $val);
                            break;
                    }
                }
            }
        }
    }

    private function buildSort(&$query, $sort)
    {
        foreach ($sort as $key => $value) {
            $query->orderBy($key, $value);
        }
    }

    /**
     * fields=id,name
     * join=teams
     * @param int $id
     * @param Array $input
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return Array
     */
    protected function getOneResult($id, $input, $query)
    {

        $query->when(Arr::has($input, 'join'), function (Builder $query) use ($input) {
            $this->buildJoin($query, $input['join']);
        });

        $query->when(Arr::has($input, 'sum'), function (Builder $query) use ($input) {
            $this->buildJoinCount($query, $input['sum']);
        });

        //\Illuminate\Support\Facades\DB::connection()->enableQueryLog();
        $data = optional($query->findOrFail($id))->toArray();
        //dd(\Illuminate\Support\Facades\DB::getQueryLog());

        if (Arr::has($input, 'fields')) {
            $data = Arr::only($data, explode(',', $input['fields']));
        }

        return $data;
    }
}
