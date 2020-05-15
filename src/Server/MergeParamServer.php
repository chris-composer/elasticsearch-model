<?php

namespace ChrisComposer\ElasticsearchModel\Server;


use Elasticsearch\ClientBuilder;

class MergeParamServer
{
    /**
     * 合成 query params
     */
    public function bodyParamsQuery($params, $values)
    {
        # add must
        if (isset($values['must'])) {
            $params['query']['bool']['must'] = $values['must'];
        }
        # add must-should
        if (isset($values['must_should'])) {
            $params['query']['bool']['must'][]['bool']['should'] = $values['must_should'];
        }
        # add must_not
        if (isset($values['must_not'])) {
            $params['query']['bool']['must_not'] = $values['must_not'];
        }
        
        return $params;
    }
}