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
    public $app;

    public function __construct(Container $c)
    {
        $this->app = $c;
    }

    public function index()
    {
        echo "绑定成功！";
    }

    public function getApp()
    {
        return $this->app;
    }

}

$c = new Container();
$t = new IndexController($c);

$i_test = new InstanceTest();
$tmp =  $t->getApp()->instance('in_test',$i_test);
$tmp = $t->getApp()->get('in_test');
$tmp->index();exit;

var_dump();exit;


//绑定
$t->getApp()->alias('Smater\Tests\HelloWorld', 'HelloWorld');
print_r($t->getApp());
//解析
$hello = $t->getApp()->make('HelloWorld');
var_dump($hello->login());exit;

//2.绑定闭包.闭包直接提供类实现方式
//$this->app->bind('HelpSpot\API', function () {
//    return new HelpSpot\API();
//});
$t->getApp()->bind('Hello',function(){
    return new HelloWorld();
});
print_r($t->getApp());
$t->getApp()->make('Hello')->index();

////1.绑定自身
//$this->app->bind('HelpSpot\API', null);

//
////闭包返回需要依赖注入的类
//$this->app->bind('HelpSpot\API', function ($app) {
//    return new HelpSpot\API($app->make('HttpClient'));
//});
//
////3. 绑定接口和实现
//$this->app->bind('Illuminate\Tests\Container\IContainerContractStub', 'Illuminate\Tests\Container\ContainerImplementationStub');



