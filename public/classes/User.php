<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * This file contains code of {User} class
 * Date: 25.02.12
 * Time: 19:50
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
 * {User} class description.
 *
 * Class: {User}
 *
 * @package default
 * @subpackage
 */
class User
{
    /** @var array */
    protected static $_allUsers = null;

    /**
     * Get all users
     *
     * @static
     * @return array
     */
    public static function getAll()
    {
        if (is_null(self::$_allUsers)) {
            $db = Db::getInstance();
            $query =
                "SELECT id, email
                 FROM users
                 ORDER BY email ASC";
            $result = $db->pdo->query($query);
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                self::$_allUsers[$row['id']] = $row['email'];
            }
        }
        return self::$_allUsers;
    }

    /**
     * Try load user by token
     *
     * @static
     * @param string $token
     * @return array
     */
    public static function loadByToken($token)
    {
        $db = Db::getInstance();
        $query = "
            SELECT id, email, role
            FROM users
            WHERE token = :token";
        $sth = $db->pdo->prepare($query);
        $sth->execute(array(':token' => $token));
        return $sth->fetch(PDO::FETCH_ASSOC);
    }
}
