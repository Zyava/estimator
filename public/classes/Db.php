<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * This file contains code of {Db} class
 * Date: 25.02.12
 * Time: 18:28
 *
 * Functions of this File:
 *
 *
 * @author  Dmitriy Zavalkin <dimzav@gmail.com>
 * @version 0.01
 * @package default
 * @subpackage
 */

/**
 * {Db} class description.
 *
 * Class: {Db}
 *
 * @package default
 * @subpackage
 */
class Db
{
    /** @var PDO */
    public $pdo;

    /** @var Db */
    static private $_instance = NULL;

    /**
     * Get class instance
     *
     * @static
     * @return Db
     */
    static function getInstance()
    {
        if (self::$_instance == NULL) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct()
    {
        $this->pdo = new PDO('mysql:host=192.168.10.183;dbname=estimator', 'root', 'root',
            array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
        );
    }

    private function __clone() {}
}
