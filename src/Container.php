<?php
/**
 * Created by PhpStorm.
 * User: chenxiansen <2545299401@qq.com>
 * Date: 2023/5/11
 * Time: 16:48
 */

namespace Smater\ExtContainer;


use Smater\ExtContainer\Contracts\Container as ContainerContract;
use Closure;

class Container implements ContainerContract
{

    //绑定的数组
    protected $bindings = [];

    //单例的实例
    protected $instances = [];

    //已经解析的实例
    protected $resolved = [];

    //已经注册的别名,函数为下标，别名为值
    /*
     * protected 'aliases' =>
        array (size=75)
          'Illuminate\Foundation\Application' => string 'app' (length=3)
          'Illuminate\Contracts\Container\Container' => string 'app' (length=3)
          'Illuminate\Contracts\Foundation\Application' => string 'app' (length=3)
          'Psr\Container\ContainerInterface' => string 'app' (length=3)
          'Illuminate\Auth\AuthManager' => string 'auth' (length=4)
          'Illuminate\Contracts\Auth\Factory' => string 'auth' (length=4)
          'Illuminate\Contracts\Auth\Guard' => string 'auth.driver' (length=11)
     */
    protected $aliases = [];

    /*
     * protected 'abstractAliases' =>
        array (size=39)
          'app' =>
            array (size=4)
              0 => string 'Illuminate\Foundation\Application' (length=33)
              1 => string 'Illuminate\Contracts\Container\Container' (length=40)
              2 => string 'Illuminate\Contracts\Foundation\Application' (length=43)
              3 => string 'Psr\Container\ContainerInterface' (length=32)
          'auth' =>
            array (size=2)
              0 => string 'Illuminate\Auth\AuthManager' (length=27)
              1 => string 'Illuminate\Contracts\Auth\Factory' (length=33)
     */
    //已经注册的别名，别名为下标，函数为数组，为什么这样 TODO
    protected $abstractAliases = [];

    //检测是否已经绑定
    public function bound($abstract)
    {
        /*
         * 返回绑定了的 和 实例化的 和 是否已经别名了的 ,
        */
        return isset($this->bindings[$abstract]) ||
            isset($this->instances[$abstract]) ||
            $this->isAlias($abstract);
    }


    public function isAlias($name)
    {
        //返回别名数组
        return isset($this->aliases[$name]);
    }

    //给函数设置别名,$abstract为抽象，$alias 为具体
    public function alias($abstract, $alias)
    {
        //别名和实例不能一样
        if($abstract === $alias)
        {
            //抽象函数和别名一样 这里面为什么abstract 和 alias不能一样 TODO
            throw new \Exception("[{$abstract}] is aliased to itself.");
        }
        $this->aliases[$alias] = $abstract;

        $this->abstractAliases[$abstract][] = $alias;

    }

    //打标签
    public function tag($abstracts, $tags)
    {
        // TODO: Implement tag() method.
    }

    //解析标签
    public function tagged($tag)
    {
        // TODO: Implement tagged() method.
    }


    //绑定到容器，常用绑定形式如下
    /*
     * 1.绑定自身
        $this->app->bind('HelpSpot\API', null);
        2.绑定闭包
        $this->app->bind('HelpSpot\API', function () {
            return new HelpSpot\API();
        });//闭包直接提供类实现方式
        $this->app->bind('HelpSpot\API', function ($app) {
            return new HelpSpot\API($app->make('HttpClient'));
        });//闭包返回需要依赖注入的类
        3. 绑定接口和实现
        $this->app->bind('Illuminate\Tests\Container\IContainerContractStub', 'Illuminate\Tests\Container\ContainerImplementationStub');
     */
    public function bind($abstract,$concrete = null,$share = false)
    {
        //绑定之前，先销毁已经绑定了的实例，别名，
        $this->dropStaleInstances($abstract);

        //如果 concrete具体类没有赋值，则把 抽象类赋值给具体类，不需要声明了
        if(is_null($concrete))
        {
            //$this->app->bind('HelpSpot\API', null); 这种绑定形式
            $concrete = $abstract;
        }

        //先处理不是闭包函数的，最后全部转化为闭包函数
        if(! $concrete instanceof Closure)
        {
            //不是闭包函数,则是如下两种形式
            /*
             *      $this->app->bind('HelpSpot\API', null);
             *      $this->app->bind('Illuminate\Tests\Container\IContainerContractStub', 'Illuminate\Tests\Container\ContainerImplementationStub');
             */
            //判断是不是字符串
            if(!is_string($concrete))
            {
                throw new TypeError(self::class.'::bind(): Argument #2 ($concrete) must be of type Closure|string|null');
            }
            //把上面两种绑定形式转化成闭包函数
            $concrete = $this->getClosure($abstract,$concrete);
        }

        //全部处理完成，写入 格式如下
        /*
         * protected 'bindings' =>
                array (size=15)
                  'Illuminate\Foundation\Mix' =>
                    array (size=2)
                      'concrete' =>
                        object(Closure)[2]
                          ...
                      'shared' => boolean true
                  'Illuminate\Foundation\PackageManifest' =>
                    array (size=2)
                      'concrete' =>
                        object(Closure)[4]
                          ...
                      'shared' => boolean true
                  'events' =>
                    array (size=2)
                      'concrete' =>
                        object(Closure)[6]
                          ...
                      'shared' => boolean true
                  'log' =>
                    array (size=2)
                      'concrete' =>
                        object(Closure)[8]
                          ...
                      'shared' => boolean true
                  'router' =>
                    array (size=2)
                      'concrete' =>
                        object(Closure)[10]
                      'shared' => boolean true
         */
        $this->bindings[$abstract] = compact('concrete','shared');
        //如果抽象类型已经在这个容器中被解析，我们将触发反弹侦听器，以便任何已经被解析的对象可以通过侦听器回调来更新对象的副本。
        if($this->resolved($abstract))
        {
            //已经解析了，需要重新绑定
            $this->rebound($abstract);
        }

    }

    //重新绑定
    protected function rebound($abstract)
    {
        //实例化
        $instance = $this->make($abstract);
        // TODO TODO TODO


    }

    //检测抽象类型是否被绑定
    protected function resolved($abstract)
    {
        //判断别名有没有，有的话转换成别名，
        if($this->isAlias($abstract))
        {
            $abstract = $this->getAlias($abstract);
        }
        //查询解析数组，和 实例数组
        return isset($this->resolved[$abstract]) || $this->instances[$abstract];

    }

    //获取别名
    protected function getAlias($abstract)
    {
        return isset($this->aliases[$abstract]) ? $this->getAlias($this->aliases[$abstract]):$abstract;
    }

    //处理闭包函数
    protected function getClosure($abstract,$concrete)
    {
        return function ($container,$parameters = []) use($abstract,$concrete){
          if($abstract == $concrete)
          {
              //$this->app->bind('HelpSpot\API', null); 这种形式 TODO build方法待研究
              return $container->build($concrete);
          }
            //剩下这种形式，$this->app->bind('Illuminate\Tests\Container\IContainerContractStub', 'Illuminate\Tests\Container\ContainerImplementationStub');
            //TODO resolve方法待研究 $raiseEvents = false 这个参数待研究
            return $container->resolve($concrete,$parameters,$raiseEvents = false);

        };

    }
    //实例化对象
    public function make($abstract, array $parameters = [])
    {
        //解析抽象类
        return $this->resolve($abstract,$parameters);
    }

    //从容器中，解析抽象类
    protected function resolve($abstract, $parameters = [], $raiseEvents = true)
    {
        //获取抽象类
        $abstract = $this->getAlias($abstract);
        if($raiseEvents)
        {
            //TODO
        }

    }


    //删除已经老的绑定了的 和 别名
    protected function dropStaleInstances($abstract)
    {
        unset($this->bindings[$abstract],$this->aliases[$abstract]);
    }

    public function get(string $id)
    {
        // TODO: Implement get() method.
    }

    public function has(string $id): bool
    {
        // TODO: Implement has() method.
    }
}