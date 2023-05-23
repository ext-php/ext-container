<?php
/**
 * Created by PhpStorm.
 * User: chenxiansen <2545299401@qq.com>
 * Date: 2023/5/23
 * Time: 11:09
 */

namespace Smater\ExtContainer;

use Exception;


class CircularDependencyException extends Exception implements ContainerExceptionInterface
{

}