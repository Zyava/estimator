<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * This file contains code of {UserLogs} class
 * Date: 25.02.12
 * Time: 18:10
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
 * {UserLogs} class description.
 *
 * Class: {UserLogs}
 *
 * @package default
 * @subpackage
 */
class UserLogs
{
    /** @var string[] */
    protected $_logMessages;

    /** @var UserLogs[] */
    static private $_instance = array();

    /** @var array */
    static private $_currentSprint = array();

    /**
     * Get user logs class instance
     *
     * @static
     * @param int $sprintId
     * @return UserLogs
     */
    static function getInstance($sprintId)
    {
        if (!isset(self::$_instance[$sprintId])) {
            self::$_instance[$sprintId] = new self($sprintId);
            self::$_currentSprint = $sprintId;
        }
        return self::$_instance[$sprintId];
    }

    /**
     * @param int $sprintId
     */
    private function __construct($sprintId)
    {
        $db = Db::getInstance();
        $query = sprintf(
            "SELECT user_id, log_text, created_date
             FROM user_logs
             WHERE sprint_id = %d
             ORDER BY created_date DESC",
            $sprintId
        );
        $result = $db->pdo->query($query);
        $this->_logMessages = $result->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Log new message
     *
     * @param int $userId
     * @param string $logMessage
     * @return PDOStatement
     */
    public function save($userId, $logMessage)
    {
        $sprintId = self::$_currentSprint;
        $db = Db::getInstance();
        $query =
            "INSERT INTO user_logs(user_id, sprint_id, log_text)
             VALUE(:user_id, :sprint_id, :log_text)";
        $sth = $db->pdo->prepare($query);
        $sth->execute(array(':user_id' => $userId, ':sprint_id' => $sprintId, ':log_text' => $logMessage));
        $this->_logMessages[] = $logMessage;

        $users = User::getAll();
        $message = sprintf('[%s] %s: %s', date('m/d/Y H:m:s'), $users[$userId], $logMessage);
        $dispatcher = Dispatcher::getInstance();
        $result = array(
            'destination' => 'sprint',
            'data' => array(
                'class'  => 'LogController',
                'method' => 'addMessage',
                'data'  => array(
                    'data' => $message
                ),
            )
        );
        $dispatcher->sendResponseToClient($result);

        return $db->pdo->query($query);
    }

    /**
     * Get all message for current sprint
     *
     * @return array|string[]
     */
    public function getMessages()
    {
        return $this->_logMessages;
    }

    private function __clone() {}
}
