<?php

namespace ChrisComposer\ElasticsearchModel\Traits;

use ChrisComposer\ElasticsearchModel\Interfaces\CustomTransformInterface;

Trait CustomTransformTrait
{
    public function custom_transform($data, CustomTransformInterface $transformer, $page = null, $limit = null)
    {
        if ($page !== null) {
            $transformer->page = $page;
        }

        if ($limit !== null) {
            $transformer->limit = $limit;
        }

        return $transformer->transform($data);
    }
}
