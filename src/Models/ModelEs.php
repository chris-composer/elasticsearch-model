<?php

namespace ElasticsearchModel\Models;

use ElasticsearchModel\Traits\CustomTransformTrait;
use ElasticsearchModel\Interfaces\CustomTransformInterface;
use ElasticsearchModel\Interfaces\ModelEsInterface;
use Elasticsearch\ClientBuilder;

/**
 * 只适合对单个索引进行操作
 * Class ModelEs
 *
 * @package App\ModelsEs
 */
class ModelEs implements ModelEsInterface
{
    use CustomTransformTrait;

    public $model; // es 连接后的对象

    protected $config; // 配置
    protected $connection; // 连接
    protected $index; // 索引
    protected $add_indexs = []; // 增加的索引
    protected $type = '_doc';

    protected $page;
    protected $limit;
    protected $from = 0;
    protected $size = 10000;

    protected $max_handles = 100; // 批次大小
    protected $retry; // 重试次数，不设默认等于你集群的节点数
    protected $refresh = false; // 强制更新参数
    
    protected $is_set_params = false; // 是否直接设置 params
    protected $params = [];
    protected $params_head = [];
    protected $params_body = [];

    public $result; // 执行结果
    public $result_transform; // 转换结果

    public function __construct()
    {
        $this->connection();
    }

    /**
     * 连接
     *
     * @return $this
     */
    protected function connection()
    {
        # 获取配置信息
        if ($this->connection) {
            $connection = $this->connection;
        }
        else {
            $connection = config('elasticsearch.default');
        }

        $this->config = config('elasticsearch.connections.' . $connection);

        # 设置 host
        $this->model = ClientBuilder::create()
            ->setHosts($this->config['hosts']);

        # 设置多线程线程数
        if ($this->max_handles) {
            $handlerParams = [
                'max_handles' => $this->max_handles
            ];
            $this->model->setHandler(ClientBuilder::defaultHandler($handlerParams));
        }

        # 设置重试次数
        if ($this->retry) {
            $this->model->setRetries($this->retry);
        }

        # 建立连接
        $this->model = $this->model->build();
    }

    /**
     * 结果格式转换
     *
     * @param CustomTransformInterface $transformer
     *
     * @return mixed
     */
    public function transform(CustomTransformInterface $transformer)
    {
        return $this->custom_transform($this->result, $transformer, $this->page, $this->limit);
    }

    /**
     * 设置 params, 包括 head + body，但是不包括 index
     * @param array $input
     */
    public function setParams(array $input)
    {
        $this->is_set_params = true;
        $this->params = $input;
    }

    /**
     * 自定义 head 字段，如：id, refresh
     *
     * @param $body
     */
    public function setHead(array $input)
    {
        $this->params_head = $input;

        return $this;
    }
    
    /**
     * 增加 index
     *
     * @param $input
     */
    public function addIndex(array $input)
    {
        $this->add_indexs = $input;

        return $this;
    }

    /**
     * 自定义 body
     *
     * @param $body
     */
    public function setBody(array $body)
    {
        $this->params_body = $body;

        return $this;
    }

    /**
     * 根据类型设置 body
     *
     * @param $bodyAggs
     *
     * @return mixed
     */
    public function setBodyWhere($bodyType, $input)
    {
        $this->params_body[$bodyType] = $input;

        return $this;
    }

    /**
     * 设置强制更新
     */
    public function setRefresh(bool $input = true)
    {
        $this->refresh = $input;
        $this->params['refresh'] = $input;

        return $this;
    }

    /**
     * 设置高亮
     * @param $input
     *
     * @return $this
     */
    public function setHighlight($input)
    {
        if (is_string($input)) {
            $this->params_body['highlight'] = [
                'fields' => [
                    $input => new \stdClass()
                ]
            ];
        }
        elseif (is_array($input)) {
            $this->params_body['highlight'] = $input;
        }

        return $this;
    }

    /**
     * 设置 from
     */
    public function setFrom(int $input)
    {
        # 处理输入错误
        $num = max(0, $input);
        $this->from = $num;
        $this->params['from'] = $num;

        return $this;
    }

    /**
     * 设置 size
     */
    public function setSize(int $input)
    {
        # 处理输入错误
        $num = max(0, $input);
        $this->size = $num;
        $this->params['size'] = $num;

        return $this;
    }

    /**
     * 设置分页
     *
     * @param int $page  默认第一页
     * @param int $limit 默认每页10条
     *
     * @return $this
     */
    public function setPaginate($page = 1, $limit = 10)
    {
        $this->page = max($page, 1);
        $this->limit = $limit;

        $this->from = ($this->page - 1) * $limit;
        $this->size = $limit;

        $this->params['from'] = $this->from;
        $this->params['size'] = $this->size;

        return $this;
    }

    /**
     * 设置统计总数
     */
    public function setTotal($field = '_id')
    {
        $this->params_body['aggs']['total'] = [
            'value_count' => [
                'field' => $field
            ]
        ];

        return $this;
    }

    /**
     * collapse 去重统计总数
     *
     * @param $field
     */
    public function setTotalCollapse($field)
    {
        $this->params_body['aggs']['total_collapse'] = [
            'cardinality' => [
                'field' => $field
            ]
        ];
    }

    /**
     * 创建 params
     */
    protected function createParams()
    {
        # set index
        $index = $this->config['prefix'] . $this->index;
        // 若有增加的索引加上去
        if ($this->add_indexs) {
            $index .= ',' . implode(',', $this->add_indexs);
        }
        $array['index'] = $index;
        
        # set type
        $array['type'] = $this->type;
        
        # 若 $is_set_params = true，则直接使用 $this->params，否则继续拼合 params_head + params_body
        if (! $this->is_set_params) {
            # set head
            if ($this->params_head) {
                foreach ($this->params_head as $k => $v) {
                    $this->params[$k] = $v;
                }
            }

            # set body
            if ($this->params_body) {
                $this->params['body'] = $this->params_body;
            }   
        }
        
        # merge
        $this->params = array_merge($array, $this->params);

        return $this;
    }

    /**
     * @param $name   : es 类库的方法
     * @param $params : 传参
     *
     * @return $this
     */
    public function __call($name, $params)
    {
        # 若是 get 方法，则 setHead()
        if ($name === 'get') {
            $this->setHead([
                'id' => $params[0]
            ]);
        }
        
        # 创建 params
        $this->createParams();

        # 获取结果
        $this->result = $this->model->$name($this->params);

        return $this;
    }
}