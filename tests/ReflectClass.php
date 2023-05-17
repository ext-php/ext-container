<?php
/**
 * Created by PhpStorm.
 * User: chenxiansen <2545299401@qq.com>
 * Date: 2023/5/17
 * Time: 14:16
 */

namespace Smater\Tests;


class ReflectClass
{
    private $foo;
    public $bar;
    const CONST_VALUE = 'Hello World';

    public function __construct() {
        $this->bar = 42;
    }

    public function method1() {
        // 方法逻辑
    }

    private function method2() {
        // 方法逻辑
    }

}


//构建反射函数需要全路径
$class = new \ReflectionClass('Smater\Tests\ReflectClass');
//输出类名称
print_r($class->getName());


//获取类的常量
$constants = $class->getConstants();
print_r($constants);

//获取类的属性
$properties = $class->getProperties();
var_dump($properties);

foreach ($properties as $property)
{
    print_r($property->getName());
}

$methods = $class->getMethods();
var_dump($methods);exit;

foreach ($methods as $method)
{
    print_r($method->getName());
}

