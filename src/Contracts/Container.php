<?php
/**
 * Created by PhpStorm.
 * User: chenxiansen <2545299401@qq.com>
 * Date: 2023/5/11
 * Time: 16:47
 */

namespace Smater\ExtContainer\Contracts;


interface Container
{

    //检测是否已经绑定
    public function bound($abstract);

    //给抽象函数设置别名
    public function alias($abstract,$alias);

    //绑定的实例打标签
    public function tag($abstracts,$tags);

    //解析打过标签的实例
    public function tagged($tag);

    //注册，并绑定到容器
    public function bind($abstract,$concrete = null,$share = false);

    //实例化
    public function make($abstract);












    public function get(string $id);

    public function has(string $id):bool;

}