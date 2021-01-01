# Laravel CrudController 平时工作开发的通用CRUD方法

Crud是通用的增删改查控制器基类。

## 查询多条记录、分页@index

这个方法返回两种数据，传入page参数返回包含__分页的数组__，否则返回__普通数组__。

### 分页数组格式：



```json
[
    'total' => int,
    'count' => int,
    'page' => int,
    'pageCount' => int,
    'data' => []
]
```

### 普通数组

```json
[]
```



传入参数形式为query传入：url/?s=&fields&join=&sort=&limit=&page=&offset=&sum=

### Query参数的介绍：

fields  ：id,name,desc  ：查询出的结果只返回给定的字段

s：{"id":1,"id":{"$gt":"1"},{"$or":{"tel":"aa"},{"name":"hehe"}}}：传入要查询的对象$开头的为操作符。

sort：{"id":"desc","name":"asc"} ：按照特定顺序排序，asc由小到大，desc由大到小

limit：10  ：分页时为每多少条，不分页时为只返回多少条

Page：1：当前多少页，不传时不进行分页。传入后忽略offset参数

Offset：0：从多少条后开始取，一般不使用分页时来使用。

join：带上关联的对象：查询时返回关联的对象信息，支持两种方法：用逗号隔开或json对象。

Sum：关联对象的总数：关联的对象有多少条记录，用"关联对象_count"为列名加到属性中。同样支持两种方法：用逗号隔开或json对象。



### $查询操作符

$or 满足包含的条件的数据都会返回来

$eq (=, 等于)

$ne (!=, 不等于)

$gt (>, 大于)

$lt (<, 小于)

$gte (>=, 大于等于)

$lte (<=, 小于等于)

$starts (LIKE val%, 开始于)

$ends (LIKE %val, 结束于)

$cont (LIKE %val%, 包含)

$excl (NOT LIKE %val%, 不包含)

$in (IN, 数组中存在的，接受一个数组)

$notin (NOT IN, 数据中不存在的，授受一个数组)

$isnull (IS NULL, 数据库中为NULL)

$notnull (IS NOT NULL, 数据库不是NULL)

$between (BETWEEN, 从 ...到...的值，接受两个值的数组)



### join参数

两种形式：

一种：tablea,tableb

二种：{"teams":{"s":{"id":1},"fields":"departmentId（外键在这里必须的）,id,name","sort":{"id":"desc"}},{"limit":1},"department"}

二种接收的参数有：关联属性名，s：查询条件，fields：只取哪些字段，sort：排序，limit：1



### sum参数

两种形式：

一种：tablea,tableb

二种：{"tablea":{"s":{"id":1},"tableb"}

数据中的属性会增加tablea_count,tableb_count



## 查询单条数据@show

返回单条数据

传入参数形式为query传入：url/?s=&fields&join=&sum=

s:参考 查询多条的s参数

Fields：参考查询多条的此参数

Join:参考查询多条的此参数

Sum:参考查询多条的此参数
