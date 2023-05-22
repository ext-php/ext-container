<?php
/**
 * Created by PhpStorm.
 * User: chenxiansen <2545299401@qq.com>
 * Date: 2023/5/11
 * Time: 16:47
 */

namespace Smater\ExtContainer\Contracts;
use Closure;

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
    public function make($abstract, array $parameters = []);

    /*
        class MyServiceProvider extends ServiceProvider
        {
            public function register()
            {
                $this->app->beforeResolving('myService', function ($app, $params) {
                    // 在解析 myService 实例之前，在容器中注册一个名为 myDependency 的依赖项
                    $app->instance('myDependency', new MyDependency());
                    return $params;
                });

                $this->app->bind('myService', function ($app) {
                    $dependency = $app->make('myDependency');
                    // 创建 myService 实例，并将 myDependency 注入到服务中
                    return new MyService($dependency);
                });
            }
        }
        在上述示例中，我们使用 beforeResolving() 方法注册了一个回调函数，用于在解析 myService 实例之前，向容器中注册名为 myDependency 的依赖项。这样，在解析 myService 实例时，就可以自动注入这个依赖项了。
        需要注意的是，beforeResolving() 方法只会在 $app->make() 或 $app->resolve() 方法解析实例时触发，如果直接使用实例化的方式，则不会触发 beforeResolving() 方法中注册的回调函数。
     */
    public function beforeResolving($abstract, Closure $callback = null);

    //注册一个正在解析的回调函数
    public function resolving($abstract, Closure $callback = null);

    //注册一个解析之后的回调函数
    public function afterResolving($abstract, Closure $callback = null);

    //检测函数是否已经解析过了
    public function resolved($abstract);







    public function get(string $id);

    public function has(string $id):bool;

}