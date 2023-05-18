<?php
/**
 * Created by PhpStorm.
 * User: chenxiansen <2545299401@qq.com>
 * Date: 2023/5/18
 * Time: 11:11
 */

namespace Smater\ExtContainer;
use Closure;
use ReflectionNamedType;

class Util
{

    public static function unwrapIfClosure($value, ...$args)
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }

    public static function getParameterClassName($parameter)
    {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        $name = $type->getName();

        if (! is_null($class = $parameter->getDeclaringClass())) {
            if ($name === 'self') {
                return $class->getName();
            }

            if ($name === 'parent' && $parent = $class->getParentClass()) {
                return $parent->getName();
            }
        }

        return $name;
    }

}