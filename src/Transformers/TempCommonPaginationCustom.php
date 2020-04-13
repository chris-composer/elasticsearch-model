<?php

namespace ElasticsearchModel\Transformers;


use ElasticsearchModel\Abstracts\CustomTransformAbstract;

class TempCommonPaginationCustom extends CustomTransformAbstract
{
    public function transform($input)
    {
        $source = $input['hits']['hits'];

        $data['data'] = $source;
        $data['meta']['pagination'] = $this->pagination($input);

        return $data;
    }
}
