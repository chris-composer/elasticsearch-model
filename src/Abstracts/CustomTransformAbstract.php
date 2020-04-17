<?php

namespace ChrisComposer\ElasticsearchModel\Abstracts;

use ChrisComposer\ElasticsearchModel\Interfaces\CustomTransformInterface;

abstract class CustomTransformAbstract implements CustomTransformInterface
{
    public $page;
    public $limit;
    public $is_highlight;

    protected function highlight($info)
    {
        $data = $info['_source'];

        # 替换高亮内容
        if (isset($info['highlight'])) {
            $highlight = $info['highlight'];

            foreach ($highlight as $k => $v) {
                $data[$k] = $v[0];
            }
        }

        $info['_source'] = $data;

        return $info;
    }

    protected function pagination($input)
    {
        $count = count($input['hits']['hits']);
        $total = $input['aggregations']['total']['value'];

        $data = [
            "total" => $total,
            "count" => $count,
            "per_page" => (int)$this->limit,
            "current_page" => (int)$this->page,
            "total_pages" => $this->limit ? ceil($total / $this->limit) : 0,
        ];

        return $data;
    }

    protected function pagination_collapse($input)
    {
        $count = count($input['hits']['hits']);
        $total = $input['aggregations']['total_collapse']['value'];

        $data = [
            "total" => $total,
            "count" => $count,
            "per_page" => (int)$this->limit,
            "current_page" => (int)$this->page,
            "total_pages" => $this->limit ? ceil($total / $this->limit) : 0,
        ];

        return $data;
    }
}