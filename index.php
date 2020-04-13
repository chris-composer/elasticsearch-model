<?php

require __DIR__ . './vendor/autoload.php';


$model = new \ElasticsearchModel\EcommerceEs();

$res = $model->setTotal()->setPaginate(0, 1)->search()
    ->transform(new \ElasticsearchModel\Transformers\TempCommonPaginationCustom());
dd($res);