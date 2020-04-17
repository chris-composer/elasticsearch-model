<?php

namespace ChrisComposer\ElasticsearchModel\Transformers;


use ChrisComposer\ElasticsearchModel\Abstracts\CustomTransformAbstract;

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
