<?php

/**
 * This file is part of dimtrovich/db-dumper".
 *
 * (c) 2024 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Dimtrovich\DbDumper\Spec;

use Closure;
use ReflectionClass;
use ReflectionMethod;
use ReflectionObject;
use ReflectionProperty;

/**
 * Testing helper.
 */
class ReflectionHelper
{
    /**
     * Creates an invoker for a private method of an object or class.
     *
     * This method allows invoking private methods of an object or class by creating
     * a closure that can be called with the method's arguments.
     *
     * @param object|string $obj    The object instance or the class name containing the private method
     * @param string        $method The name of the private method to invoke
     *
     * @return Closure A closure that can be used to invoke the private method
     *
     * @throws ReflectionException If the method does not exist or is not accessible
     */
    public static function getPrivateMethodInvoker($obj, string $method): Closure
    {
        $refMethod = new ReflectionMethod($obj, $method);
        $refMethod->setAccessible(true);
        $obj = (gettype($obj) === 'object') ? $obj : null;

        return static fn (...$args) => $refMethod->invokeArgs($obj, $args);
    }

    /**
     * Gets an accessible ReflectionProperty for a given object or class and property name.
     *
     * This method creates a ReflectionProperty for the specified property and makes it accessible,
     * allowing access to protected and private properties.
     *
     * @param object|string $obj      The object instance or the class name
     * @param string        $property The name of the property to reflect
     *
     * @return ReflectionProperty An accessible ReflectionProperty instance for the specified property
     *
     * @throws ReflectionException If the property does not exist
     */
    public static function getAccessibleRefProperty($obj, string $property): ReflectionProperty
    {
        $refClass = is_object($obj) ? new ReflectionObject($obj) : new ReflectionClass($obj);

        $refProperty = $refClass->getProperty($property);
        $refProperty->setAccessible(true);

        return $refProperty;
    }

    /**
     * Sets the value of a private property on an object or class.
     *
     * @param object|string $obj      The object instance or the class name
     * @param string        $property The name of the private property to set
     * @param mixed         $value    The value to set for the private property
     *
     * @throws ReflectionException If the property does not exist or is not accessible
     */
    public static function setPrivateProperty($obj, string $property, mixed $value): void
    {
        $refProperty = self::getAccessibleRefProperty($obj, $property);

        if (is_object($obj)) {
            $refProperty->setValue($obj, $value);
        } else {
            $refProperty->setValue(null, $value);
        }
    }

    /**
     * Retrieves the value of a private property from an object or class.
     *
     * @param object|string $obj      The object instance or the class name
     * @param string        $property The name of the private property to access
     *
     * @return mixed The value of the private property
     */
    public static function getPrivateProperty($obj, string $property): mixed
    {
        $refProperty = self::getAccessibleRefProperty($obj, $property);

        return is_string($obj) ? $refProperty->getValue() : $refProperty->getValue($obj);
    }
}
