<?php

namespace ComposerTest;


class ComposerTest1
{
    public function index()
    {

        echo '我总是这么帅' . PHP_EOL;
        echo "一次就好，我带你去看天荒地老" . PHP_EOL;
        echo "在阳光灿烂的日子里开怀大笑" . PHP_EOL;
        echo "在自由自在的空气里吵吵闹闹" . PHP_EOL;
        echo "你可知道我唯一的想要" . PHP_EOL;

        $this->config();
    }

    public function config()
    {
        dd("config: composer_test.table_name = " . config('composer_test.table_name'));
    }
}