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
    protected $type = '_doc';

    protected $page;
    protected $limit;
    protected $from = 0;
    protected $size = 10000;

    protected $max_handles = 100; // 批次大小
    protected $retry; // 重试次数，不设默认等于你集群的节点数
    protected $refresh = false; // 强制更新参数

    protected $params = [];
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
     * 自定义 body
     *
     * @param $body
     */
    public function setBody($body)
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
     * 去重统计总数
     *
     * @param $field
     */
    public function setTotalCollapse($field)
    {
        $this->params_body['aggs'] = [
            'total_collapse' => [
                'cardinality' => [
                    'field' => $field
                ]
            ],
        ];
    }

    /**
     * 创建 params
     */
    protected function createParams()
    {
        $this->params['index'] = $this->config['prefix'] . $this->index;
        $this->params['type'] = $this->type;
        $this->params['body'] = $this->params_body;

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
        # 创建 params
        $this->createParams();

        # 获取结果
        $this->result = $this->model->$name($this->params);

        return $this;
    }
}