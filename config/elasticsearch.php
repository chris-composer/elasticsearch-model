<?php

return [
    'default' => env('ES_CONNECTION', 'elasticsearch'),

    'connections' => [
        'elasticsearch' => [
            'hosts' =>  explode(',', env('ES_HOST', 'http://10.0.0.65:2020')),
            'prefix' => ''
        ]
    ],
];
