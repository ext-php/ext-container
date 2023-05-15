<?php
/**
 * Created by PhpStorm.
 * User: chenxiansen <2545299401@qq.com>
 * Date: 2023/5/15
 * Time: 10:14
 */

namespace Smater\Tests;


use Smater\ExtContainer\Container;

class IndexController
{

    public function index()
    {
        echo "绑定成功！";
    }

}

$test = new IndexController();
$app = new Container();
$app->bind("test",$test);

$app->make("test")->index();

