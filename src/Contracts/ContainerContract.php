<?php
/**
 * Created by PhpStorm.
 * User: chenxiansen <2545299401@qq.com>
 * Date: 2023/5/11
 * Time: 16:47
 */

namespace Smater\ExtContainer\Contracts;


interface ContainerContract
{

    //绑定
    public function bind($name,$args = []);

    //实例化
    public function make($name);

}