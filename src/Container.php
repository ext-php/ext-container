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
use ReflectionClass;
use Exception;
use ReflectionType;
use ReflectionParameter;

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
    //已经注册的别名，别名为下标，函数为数组，
    protected $abstractAliases = [];

    //全局解析回调
    protected $globalBeforeResolvingCallbacks = [];

    //解析函数前的回调
    protected $beforeResolvingCallbacks = [];

    //上下文绑定实例
    public $contextual = [];

    //当前实例化堆栈
    protected $buildStack = [];

    //参数覆盖堆栈
    protected $with = [];

    //拓展闭包
    protected $extenders = [];

    //全局解析回调
    protected $globalResolvingCallbacks = [];

    //全局解析后的callback
    protected $globalAfterResolvingCallbacks = [];

    //根据类的类型解析callback
    protected $resolvingCallbacks = [];

    //解析后的回调类型
    protected $afterResolvingCallbacks = [];

    //重新绑定的回调
    protected $reboundCallbacks = [];

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
        //
    }

    //解析标签
    public function tagged($tag)
    {
        //
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
    public function bind($abstract,$concrete = null,$shared = false)
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

        foreach ($this->getReboundCallbacks($abstract) as $callback)
        {
            $callback($this,$instance);
        }

    }

    protected function getReboundCallbacks($abstract)
    {
        return $this->reboundCallbacks[$abstract] ?? [];
    }


    //检测抽象类型是否被绑定
    public function resolved($abstract)
    {
        //判断别名有没有，有的话转换成别名，
        if($this->isAlias($abstract))
        {
            $abstract = $this->getAlias($abstract);
        }
        //查询解析数组，和 实例数组
        return isset($this->resolved[$abstract]) || isset($this->instances[$abstract]);

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
              //$this->app->bind('HelpSpot\API', null); 这种形式 build方法待研究 TODO
              return $container->build($concrete);
          }
            //剩下这种形式，$this->app->bind('Illuminate\Tests\Container\IContainerContractStub', 'Illuminate\Tests\Container\ContainerImplementationStub');
            //resolve方法待研究 $raiseEvents = false 这个参数待研究 TODO
           return $container->resolve($concrete,$parameters,$raiseEvents = false);

        };

    }

    //注册单例共享实例
    public function singleton($abstract,$concrete)
    {

        $this->bind($abstract,$concrete,true);
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
        /*
        *  protected 'aliases' =>
           array (size=75)
             'Illuminate\Foundation\Application' => string 'app' (length=3)
             'Illuminate\Contracts\Container\Container' => string 'app' (length=3)
             'Illuminate\Contracts\Foundation\Application' => string 'app' (length=3)
             'Psr\Container\ContainerInterface' => string 'app' (length=3)
             'Illuminate\Auth\AuthManager' => string 'auth' (length=4)
             'Illuminate\Contracts\Auth\Factory' => string 'auth' (length=4)
             'Illuminate\Contracts\Auth\Guard' => string 'auth.driver' (length=11)
        */
        $abstract = $this->getAlias($abstract);
        //$abstract = app

        //触发解析前回调
        if($raiseEvents)
        {
            $this->fireBeforeResolvingCallbacks($abstract, $parameters);
        }

        //$abstract = app 处理和 app相关的
        $concrete = $this->getContextualConcrete($abstract);
        //abstractAliases 数组还有未编译的依赖，或者参数不为空
        $needsContextualBuild = ! empty($parameters) || ! is_null($concrete);
        /*
        * 如果该类型的实例当前作为单例进行管理，我们将只是返回一个现有的实例，而不是实例化新的实例，因此，开发人员可以每次都使用相同的对象实例。
        */
        //没有上下文依赖，并且instances里面已经有该实例，则直接返回
        if(isset($this->instances[$abstract]) && ! $needsContextualBuild)
        {
            return $this->instances[$abstract];
        }
        //参数依赖压如堆栈
        $this->with[] = $parameters;
        //concrete 是 null
        if(is_null($concrete))
        {
            $concrete = $this->getConcrete($abstract);
        }
        /*
         * 我们已经准备好实例化所注册的具体类型的实例绑定。这将实例化类型，并解析任何类型，递归地“嵌套”依赖项，直到所有依赖项都得到解决。
         */
        if($this->isBuildable($concrete,$abstract))
        {
            //能构建
            $object = $this->build($concrete);
        }else{
            //否则递归编译make
            $object = $this->make($concrete);
        }

        //判断该类有没有拓展
        foreach ($this->getExtenders($abstract) as $extender)
        {
            $object = $extender($object, $this);
        }

        /*
         * 如果请求的类型被注册为单例类型，我们将需要关闭缓存实例在“内存”中，这样我们就可以稍后返回它，而不必创建一个对象在每次后续请求时的全新实例。
         */
        if($this->isShared($abstract) && ! $needsContextualBuild)
        {
            $this->instances[$abstract] = $object;
        }

        //解析中回调
        if($raiseEvents)
        {
            $this->fireResolvingCallbacks($abstract,$object);
        }

        $this->resolved[$abstract] = true;

        array_pop($this->with);

        return $object;

    }

    //解析中的回调
    protected function fireResolvingCallbacks($abstract, $object)
    {
        $this->fireCallbackArray($object, $this->globalResolvingCallbacks);

        $this->fireCallbackArray(
            $object, $this->getCallbacksForType($abstract, $object, $this->resolvingCallbacks)
        );

        $this->fireAfterResolvingCallbacks($abstract, $object);
    }

    protected function fireCallbackArray($object, array $callbacks)
    {
        foreach ($callbacks as $callback) {
            $callback($object, $this);
        }
    }

    protected function getCallbacksForType($abstract, $object, array $callbacksPerType)
    {
        $results = [];

        foreach ($callbacksPerType as $type => $callbacks) {
            if ($type === $abstract || $object instanceof $type) {
                $results = array_merge($results, $callbacks);
            }
        }

        return $results;
    }

    //解析
    protected function fireAfterResolvingCallbacks($abstract, $object)
    {
        $this->fireCallbackArray($object, $this->globalAfterResolvingCallbacks);

        $this->fireCallbackArray(
            $object, $this->getCallbacksForType($abstract, $object, $this->afterResolvingCallbacks)
        );
    }

    //检测是否是单例
    protected function isShared($abstract)
    {
        return isset($this->instances[$abstract]) || (isset($this->bindings[$abstract]["shared"]) && $this->bindings[$abstract]["shared"] == true);

    }


    //获取拓展
    protected function getExtenders($abstract)
    {
        return $this->extenders[$this->getAlias($abstract)] ?? [];
    }

    //构建实例化
    public function build($concrete)
    {
        //如果是闭包函数
        if($concrete instanceof Closure)
        {
            //如果要解析的依赖项是可调用的闭包函数（Closure），则调用闭包函数传入当前实例对象和最后一个参数覆盖值，生成相应的依赖项并返回。
            return $concrete($this,$this->getLastParameterOverride());
        }

        //不是闭包
        try{
            //反射构建函数
            $reflector = new ReflectionClass($concrete);
        }catch (Exception $e)
        {
            throw new Exception("Target class [$concrete] does not exist.");
        }

        //判断能否实例化
        if(! $reflector->isInstantiable())
        {
            //不能实例化，包含私有的构造函数等
            return $this->notInstantiable($concrete);
        }
        //可以实例化，写入堆栈
        $this->buildStack[] = $concrete;
        //获取构造函数
        $constructor = $reflector->getConstructor();
        //构造函数为空，则没有其他多余的依赖关系，可以直接返回实例对象
        if(is_null($constructor))
        {
            array_pop($this->buildStack);

            return new $concrete;
        }

        //获取依赖参数
        $dependencies = $constructor->getParameters();

        try{

            $instances = $this->resolveDependencies($dependencies);
        }catch (Exception $e)
        {
            array_pop($this->buildStack);

            throw $e;
        }

        array_pop($this->buildStack);
        //构建对象实例
        return $reflector->newInstanceArgs($instances);

    }

    //解析构造函数的依赖关系
    protected function resolveDependencies(array $dependencies)
    {
        $results = [];

        foreach ($dependencies as $dependency)
        {
            /*
             * 如果依赖项对这个特定的构建有覆盖，我们将使用而不是作为值。否则，我们将继续运行,并让反射来决定结果。
             */
            // TODO 待理解
            if ($this->hasParameterOverride($dependency)) {
                $results[] = $this->getParameterOverride($dependency);

                continue;
            }

            /*
            * 如果类为空，则意味着依赖项是字符串或其他类型，私有类型，我们无法解析，因为它不是类和因为我们没有地方可去，所以我们只会出现一个错误。
            */
            //TODO TODO TODO 待理解
            $result = is_null(Util::getParameterClassName($dependency)) ? $this->resolvePrimitive($dependency) : $this->resolveClass($dependency);
            //包含 ...$args 参数
            if ($dependency->isVariadic()) {
                $results = array_merge($results, $result);
            } else {
                $results[] = $result;
            }

        }

        return $results;

    }

    //解析参数类
    protected function resolveClass(ReflectionParameter $parameter)
    {
        try{
            return $parameter->isVariadic()?$this->resolveVariadicClass($parameter):$this->make(Util::getParameterClassName());

        }catch (Exception $e)
        {
            if($parameter->isDefaultValueAvailable())
            {
                array_pop($this->with);

                return $parameter->getDefaultValue();
            }

            if($parameter->isVariadic())
            {
                array_pop($this->with);

                return [];
            }

            throw $e;
        }

    }

    //解析 ...参数类
    protected function resolveVariadicClass(ReflectionParameter $parameter)
    {
        $className = Util::getParameterClassName($parameter);

        $abstract = $this->getAlias($className);

        if (! is_array($concrete = $this->getContextualConcrete($abstract))) {
            return $this->make($className);
        }

        return array_map(function ($abstract) {
            return $this->resolve($abstract);
        }, $concrete);

    }


    //解析非类暗示的原语依赖。
    protected function resolvePrimitive(ReflectionParameter $parameter)
    {
        //判断依赖是否在上下文里
        if(! is_null($concrete = $this->getContextualConcrete('$'.$parameter->getName())))
        {
            return Util::unwrapIfClosure($concrete, $this);
        }

        //获取默认值
        if($parameter->isDefaultValueAvailable())
        {
            return $parameter->getDefaultValue();
        }

        //如果是 ...$args 这种的
        if($parameter->isVariadic())
        {
            return [];
        }

        $this->unresolvablePrimitive($parameter);

    }

    //不可解析的
    protected function unresolvablePrimitive(ReflectionParameter $parameter)
    {
        $message = "Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}";

        throw new Exception($message);
    }


    protected function getParameterOverride($dependency)
    {
        return $this->getLastParameterOverride()[$dependency->name];
    }


    protected function hasParameterOverride($dependency)
    {
        return array_key_exists(
            $dependency->name, $this->getLastParameterOverride()
        );
    }

    protected function getLastParameterOverride()
    {
        return count($this->with) ? end($this->with) : [];
    }

    //不能实例化
    protected function notInstantiable($concrete)
    {
        //构建堆栈不为空
        if(! empty($this->buildStack))
        {
            $previous = implode(', ', $this->buildStack);

            $message = "Target [$concrete] is not instantiable while building [$previous].";
        }else{
            $message = "Target [$concrete] is not instantiable.";
        }

        throw new Exception("$message");
    }


    //判断能否构建
    protected function isBuildable($abstract,$concrete)
    {
        return $concrete === $abstract || $concrete instanceof Closure;

    }

    //获取具体类
    protected function getConcrete($abstract)
    {
        /*
         * 如果没有为类型注册的解析器或具体类型，我们将假设每个类型都是一个具体名称，并尝试按原样解析它，因为容器应该能够自动解析具体类型。
         * protected 'bindings' =>
            array (size=69)
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
         */
        //存在则返回具体类
        if(isset($this->bindings[$abstract]))
        {
            return $this->bindings[$abstract]["concrete"];
        }
        //不存在则返回原有类
        return $abstract;
    }

    //获取实例
    //$abstract = app 这种
    protected function getContextualConcrete($abstract)
    {

        //判断当前正在解析的实例有没有上下文正在进行编译
        if(! is_null($binding = $this->findInContextualBindings($abstract)))
        {
            return $binding;
        }

        //如果上下文都没有了
        /*
        *  protected 'abstractAliases' =>
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
        //判断abstractAliases数据有没有数据，如果没有则编译完了，
        if(empty($this->abstractAliases[$abstract]))
        {
            //依赖解析完成
            return;
        }

        //abstractAliases还有依赖没有解析完成
        foreach ($this->abstractAliases[$abstract] as $alias)
        {
            if(! is_null($binding = $this->findInContextualBindings($alias)))
            {
                return $binding;
            }
        }

    }

    //查询上下文绑定数组
    protected function findInContextualBindings($abstract)
    {
        return $this->contextual[end($this->buildStack)][$abstract] ?? null;
    }

    //解耦解析前的依赖
    protected function fireBeforeResolvingCallbacks($abstract,$parameters = [])
    {
        //$abstract = app
        $this->fireBeforeCallbackArray($abstract,$parameters,$this->globalBeforeResolvingCallbacks);

        foreach ($this->beforeResolvingCallbacks as $type => $callbacks)
        {
            if ($type === $abstract || is_subclass_of($abstract, $type))
            {
                $this->fireBeforeCallbackArray($abstract, $parameters, $callbacks);
            }
        }

    }

    //待研究
    protected function fireBeforeCallbackArray($abstract, $parameters, array $callbacks)
    {
        foreach ($callbacks as $callback)
        {
            $callback($abstract,$parameters,$this);
        }

    }

    //
    public function resolving($abstract, Closure $callback = null)
    {
        if (is_string($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        if (is_null($callback) && $abstract instanceof Closure) {
            $this->globalResolvingCallbacks[] = $abstract;
        } else {
            $this->resolvingCallbacks[$abstract][] = $callback;
        }
    }

    //解析注册前的依赖
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
    public function beforeResolving($abstract, Closure $callback = null)
    {
        /*
         * protected 'aliases' =>
            array (size=75)
              'Illuminate\Foundation\Application' => string 'app' (length=3)
              'Illuminate\Contracts\Container\Container' => string 'app' (length=3)
              'Illuminate\Contracts\Foundation\Application' => string 'app' (length=3)
              'Psr\Container\ContainerInterface' => string 'app' (length=3)
         */
        if(isset($abstract))
        {
            $abstract = $this->getAlias($abstract);
        }
        //如果别名是闭包函数
        if($abstract instanceof  Closure && is_null($callback))
        {
            $this->globalBeforeResolvingCallbacks[] = $abstract;
        }else{
            //写入解析回调数组
            $this->beforeResolvingCallbacks[$abstract][] = $callback;
        }

    }

    public function afterResolving($abstract, Closure $callback = null)
    {
        if (is_string($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        if ($abstract instanceof Closure && is_null($callback)) {
            $this->globalAfterResolvingCallbacks[] = $abstract;
        } else {
            $this->afterResolvingCallbacks[$abstract][] = $callback;
        }
    }

    //删除已经老的绑定了的 和 别名
    protected function dropStaleInstances($abstract)
    {
        unset($this->bindings[$abstract],$this->aliases[$abstract]);
    }

    /*
     * protected 'instances' =>
            array (size=40)
              'path' => string 'D:\phpstudy_pro\WWW\My_Extensions\testlaravel\app' (length=49)
              'path.base' => string 'D:\phpstudy_pro\WWW\My_Extensions\testlaravel' (length=45)
              'path.config' => string 'D:\phpstudy_pro\WWW\My_Extensions\testlaravel\config' (length=52)
              'path.public' => string 'D:\phpstudy_pro\WWW\My_Extensions\testlaravel\public' (length=52)
              'path.storage' => string 'D:\phpstudy_pro\WWW\My_Extensions\testlaravel\storage' (length=53)
              'path.database' => string 'D:\phpstudy_pro\WWW\My_Extensions\testlaravel\database' (length=54)
              'path.resources' => string 'D:\phpstudy_pro\WWW\My_Extensions\testlaravel\resources' (length=55)
              'path.bootstrap' => string 'D:\phpstudy_pro\WWW\My_Extensions\testlaravel\bootstrap' (length=55)
              'path.lang' => string 'D:\phpstudy_pro\WWW\My_Extensions\testlaravel\lang' (length=50)
              'app' =>
                &object(Illuminate\Foundation\Application)[3]
              'Illuminate\Container\Container' =>
                &object(Illuminate\Foundation\Application)[3]
              'events' =>
                object(Illuminate\Events\Dispatcher)[27]
                  protected 'container' =>
                    &object(Illuminate\Foundation\Application)[3]
                  protected 'listeners' =>
                    array (size=7)
                      ...
                  protected 'wildcards' =>
                    array (size=0)
                      ...
                  protected 'wildcardsCache' =>
                    array (size=15)
                      ...
                  protected 'queueResolver' =>
                    object(Closure)[28]
                      ...
     */
    //注册一个共享的实例到容器
    public function instance($abstract, $instance)
    {
        //清除抽象类别名
        $this->removeAbstractAlias($abstract);

        //判断是否绑定
        $isbound = $this->bound($abstract);

        //清除别名组
        unset($this->aliases[$abstract]);

        $this->instances[$abstract] = $instance;
        //我们将检查以确定此类型之前是否绑定过，以及是否绑定过 我们将触发与容器及其注册的反弹回调 可以用这里已经解决的消费类来更新。
        if($isbound)
        {
            $this->rebound($abstract);
        }

        return $instance;


    }

    //清除抽象类别名
    protected function removeAbstractAlias($abstract)
    {
        //如果别名没有，那后面的别名组也没有数据
        if(! isset($this->aliases[$abstract]))
        {
            return;
        }
        /*
         * protected 'abstractAliases' =>
            array (size=40)
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
              'auth.driver' =>
                array (size=1)
                  0 => string 'Illuminate\Contracts\Auth\Guard' (length=31)
         */
        foreach ($this->abstractAliases as $ab => $aliases)
        {
            foreach ($aliases as $k =>$alias)
            {
                if($alias == $abstract)
                {
                    unset($this->abstractAliases[$ab][$k]);
                }

            }
        }


    }

    //获取实例，如 instances里面的实例
    public function get(string $id)
    {
       try{
           return $this->resolve($id);
       }catch (Exception $e)
       {
            if($this->has($id) || $e instanceof CircularDependencyException)
            {
                throw $e;
            }

           throw new EntryNotFoundException($id, is_int($e->getCode()) ? $e->getCode() : 0, $e);

       }
    }

    //判断是否包含
    public function has(string $id): bool
    {
        return $this->bound($id);

    }
}