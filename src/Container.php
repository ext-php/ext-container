<?php
/**
 * Created by PhpStorm.
 * User: chenxiansen <2545299401@qq.com>
 * Date: 2023/5/11
 * Time: 16:48
 */

namespace Smater\ExtContainer;


use Smater\ExtContainer\Contracts\ContainerContract;

class Container implements ContainerContract
{
    //绑定数组
    protected $binds = [];

    //绑定到容器
    public function bind($name,$args = [])
    {
        $this->binds[$name] = $name;
        var_dump($this->binds);exit;
    }

    //实例化对象
    public function make($name)
    {
        return $this->binds[$name];
    }
}