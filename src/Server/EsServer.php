<?php

namespace ChrisComposer\ElasticsearchModel\Server;


use Elasticsearch\ClientBuilder;

class EsServer
{
    public static function init($connection = null, array $option = [])
    {
        # 获取配置信息
        if ($connection) {
            $config = config('elasticsearch.connections.' . $connection);
        } else {
            $config = config('elasticsearch.connections.' . config('elasticsearch.default'));
        }

        # 设置 hosts
        $es = ClientBuilder::create()
            ->setHosts($config['hosts']);

        # 设置多线程线程数
        if (isset($option['max_handles'])) {
            $handlerParams = [
                'max_handles' => $option['max_handles']
            ];
            $es->setHandler(ClientBuilder::defaultHandler($handlerParams));
        }

        # 设置重试次数
        if (isset($option['retry'])) {
            $es->setRetries($option['retry']);
        }

        # 建立连接
        return $es->build();
    }
}