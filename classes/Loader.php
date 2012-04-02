<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * This file contains code of {Loader} class
 * Date: 25.02.12
 * Time: 21:56
 *
 * Functions of this File:
 *
 *
 * @author  Dmitriy Zavalkin <dimzav@gmail.com>
 * @version 0.01
 * @package default
 * @subpackage
 */

set_include_path(
    get_include_path() . PATH_SEPARATOR
    . dirname(__FILE__) . PATH_SEPARATOR
    . realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'controllers')
);

/**
 * {Loader} class description.
 *
 * Class: {Loader}
 *
 * @package default
 * @subpackage
 */
class Loader
{
    public static function registerAutoload()
    {
        return spl_autoload_register(array(__CLASS__, 'includeClass'));
    }

    public static function unregisterAutoload()
    {
        return spl_autoload_unregister(array(__CLASS__, 'includeClass'));
    }

    /**
     * Try load class
     *
     * @static
     * @param string $class
     * @throws Exception
     */
    public static function includeClass($class)
    {
        include(strtr($class, '_\\', '//') . '.php');
        if (!class_exists($class)) {
            throw new Exception("Class {$class} can't be loaded by autoloader");
        }
    }
}
