<?php

namespace ChrisComposer\ElasticsearchModel\Traits;

use ChrisComposer\ElasticsearchModel\Models\ModelEs;

Trait SoftDeleteEsTrait
{
    protected $is_soft_delete = true; // 是否软删除，默认：true
    protected $column_delete = 'deleted_at'; // 软删除字段，默认：deleted_at
    protected $is_only_trashed = false; // 是否只包含软删除，默认：false
    protected $__call_name;
    protected $__call_params;

    /**
     * @param $name   : es 类库的方法
     * @param $params : 传参
     *
     * @return $this
     */
    public function __call($name, $params): ModelEs
    {
        $this->__call_name = $name;
        $this->__call_params = $params;
        
        # 若是 get 方法 || delete 方法，若有传参，则使用作为设置 id
        if ($name === 'get' || $name === 'delete') {
            if ($params) {
                $this->setHead([
                    'id' => $params[0]
                ]);
            }
        }

        # 创建 params
        $this->createParams();

        # 处理软删除
        $this->handle_soft_delete($name);

        # 获取结果
        $this->result = $this->model->$name($this->params);

        return $this;
    }

    protected function handle_soft_delete(&$__call_name)
    {
        $array_out = [
            'get'
        ];
        
        $array_call_name = [
            'delete', 
            'deleteByQuery', 
            'restore' // 恢复删除
        ];

        # 若软删除功能开启
        if ($this->is_soft_delete) {
            # 若在允许的 es 方法内
            if (! in_array($__call_name, $array_out)) {
                # 处理 __call_name
                if (in_array($__call_name, $array_call_name)) {
                    $method = 'handle_soft_delete_call_name_' . $__call_name;
                    $this->$method($__call_name);
                }
                ## 若是查询，则不包含 deleted_at 有值的字段
                else {
                    $attr = $this->is_only_trashed ? 'must' : 'must_not';

                    $this->params['body']['query']['bool'][$attr][] = [
                        'exists' => [
                            'field' => $this->column_delete
                        ]
                    ];
                }
            }
        }
    }

    /**
     * 开启 / 关闭软删除
     */
    public function switchTrashed(bool $status)
    {
        $this->is_soft_delete = $status;
        
        return $this;
    }

    /**
     * 只包含软删除
     */
    public function onlyTrashed()
    {
        $this->is_only_trashed = true;
        
        return $this;
    }

    // ********** 处理 __call_name start **********

    /**
     * 处理 __call_name = delete
     */
    protected function handle_soft_delete_call_name_delete(&$__call_name)
    {
        # delete 方法变 update
        $__call_name = 'update';
        $time = time();
        $value = date('Y-m-d', $time) . 'T' . date('H:i:s', $time) . '.000Z';
        
        $this->params['body']['doc'][$this->column_delete] = $value;
    }

    /**
     * 处理 __call_name = deleteByQuery
     */
    protected function handle_soft_delete_call_name_deleteByQuery(&$__call_name)
    {
        # delete_by_query 方法变 update_by_query
        $__call_name = 'updateByQuery';

        $time = time();
        $value = date('Y-m-d', $time) . 'T' . date('H:i:s', $time) . '.000Z';

        $this->params['body']['script'] = [
            "source" => "
                ctx._source['{$this->column_delete}'] = '{$value}';
            "
        ];
    }

    /**
     * 恢复删除
     * 尽量使用 update 恢复（即不使用 body），update_by_query 暂时存在性能瓶颈
     * 处理 __call_name = restore
     */
    protected function handle_soft_delete_call_name_restore(&$__call_name)
    {
        if (isset($this->params['body'])) {
            $__call_name = 'updateByQuery';
            $this->params['body']['script'] = [
                "source" => "
                    ctx._source['{$this->column_delete}'] = null;
                "
            ];
        }
        else {
            $__call_name = 'update';
            if ($this->__call_params) {
                $this->params['id'] = $this->__call_params[0];
            }
            $this->params['body']['doc'][$this->column_delete] = null;
        }
    }

    // ********** 处理 __call_name end **********
}
