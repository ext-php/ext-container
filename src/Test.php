<?php
/**
 * Created by PhpStorm.
 * User: chenxiansen <2545299401@qq.com>
 * Date: 2023/5/11
 * Time: 16:53
 */

namespace Smater\ExtContainer;

class Test
{
    public function index()
    {
        echo "绑定成功！";
    }

}

$test = new Test();
$app = new Container();
$app->bind($test);

$app->make($test);

