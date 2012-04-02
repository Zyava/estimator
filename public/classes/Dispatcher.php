<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * This file contains code of {Dispatcher} class
 * Date: 26.02.12
 * Time: 0:12
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
 * {Dispatcher} class description.
 *
 * Class: {Dispatcher}
 *
 * @package default
 * @subpackage
 */
class Dispatcher
{
    /** @var Dispatcher */
    static private $_instance = NULL;

    /** @var PHPWebSocket */
    protected $_server;

    /** @var int */
    protected $_clientId;

    /**
     * Get class instance
     *
     * @static
     * @return Dispatcher
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
    }

    private function __clone() {}

    /**
     * Set web socket server
     *
     * @param PHPWebSocket $server
     * @return Dispatcher
     */
    public function setServer($server)
    {
        $this->_server = $server;
        return $this;
    }

    /**
     * Get web socket server
     *
     * @return PHPWebSocket
     */
    public function getServer()
    {
        return $this->_server;
    }

    /**
     * When a client sends data to the server
     *
     * @param int $clientId
     * @param string $json
     * @param int $messageLength
     * @param bool $binary
     * @return mixed
     */
    public function wsOnMessage($clientId, $json, $messageLength, $binary)
    {
        $this->_clientId = $clientId;

        $jsonArray = json_decode($json, true);

        $disconnectClient = true;

        if (!isset($this->getServer()->wsClients[$clientId]['user_data'])) {
            if (isset($jsonArray['token'])) {
                if (($user = User::loadByToken($jsonArray['token']))
                    && ($sprintId = $jsonArray['sprint_id'])
                ) {
                    $this->getServer()->wsClients[$clientId]['sprint'] = $sprintId;
                    $this->getServer()->wsClients[$clientId]['user_data'] = $user;

                    $log = UserLogs::getInstance($sprintId);
                    $log->save($user['id'], "User has logged to sprint.");
                    $this->getServer()->wsSendJson($clientId, array('result' => 'authorized'));

                    $this->sendActiveUsers('sprint');
                    return;
                }
            }
        } else {
            $disconnectClient = false;
        }

        // disconnect not authorized client
        if ($disconnectClient) {
            $this->getServer()->wsSendJson($clientId, array('result' => 'not authorized'));
            $this->getServer()->wsClose($clientId);
            return;
        }

        $sprintId = isset($this->getServer()->wsClients[$clientId]['sprint']) ?
            $this->getServer()->wsClients[$clientId]['sprint'] : 0;

        // dispatcher
        if (isset($jsonArray['class']) && isset($jsonArray['method'])) {
            try {
                $controller = new $jsonArray['class']();
                $actionData = isset($jsonArray['data']) ? $jsonArray['data'] : array();
                $result = $controller->{$jsonArray['method']}($sprintId, $actionData,
                    $this->getServer()->wsClients[$clientId],
                    $clientId
                );
                $this->sendResponseToClient($result, $sprintId, $clientId);
            } catch(Exception $e) {
                $this->getServer()->wsSendJson($clientId, array('result' => $e->getMessage()));
            }
        } else {
            $this->getServer()->wsSendJson($clientId, array('result' => 'class and/or method not set'));
        }
    }

    /**
     * When a client connects
     *
     * @param int $clientId
     */
    public function wsOnOpen($clientId)
    {
        $ip = long2ip($this->getServer()->wsClients[$clientId][6]);

        $this->getServer()->log("$ip ($clientId) has connected.");
    }

    /**
     * When a client closes or lost connection
     *
     * @param int $clientId
     * @param $status
     */
    public function wsOnClose($clientId, $status)
    {
        $ip = long2ip($this->getServer()->wsClients[$clientId][6]);

        $this->getServer()->log("$ip ($clientId) has disconnected.");

        $this->sendActiveUsers('sprint_except_sender', $clientId);
    }

    /**
     * Send list of active users for current sprint
     *
     * @param string $destination
     * @param int $clientId
     */
    public function sendActiveUsers($destination, $clientId = null)
    {
        $activeUsersForSprint = array();
        foreach ($this->getServer()->wsClients as $id => $client) {
            if ($destination == 'sprint_except_sender') {
                if ($clientId != $id) {
                    // send a quit notice to everyone but the person who quit
                    $activeUsersForSprint[$client['sprint']][] = $client['user_data']['email'];
                }
            } else {
                $activeUsersForSprint[$client['sprint']][] = $client['user_data']['email'];
            }
        }

        foreach ($activeUsersForSprint as $sprintId => $activeUsers) {
            $activeUsers = array_unique($activeUsers);
            $result = array(
                'destination' => $destination,
                'data' => array(
                    'class'  => 'UserController',
                    'method' => 'showUsers',
                    'data'  => $activeUsers
                )
            );
            $this->sendResponseToClient($result, $sprintId);
        }
    }

    /**
     * Send response to client
     *
     * @param array $result
     * @param int $sprintId
     * @param int $clientId
     */
    public function sendResponseToClient($result, $sprintId = null, $clientId = null)
    {
        if (is_null($clientId)) {
            $clientId = $this->_clientId;
        }
        if (is_null($sprintId)) {
            $sprintId = $this->getServer()->wsClients[$clientId]['sprint'];
        }

        if (isset($result['destination'])) {
            switch($result['destination']) {
                case 'sender':
                    $this->getServer()->wsSendJson($clientId, $result['data']);
                    break;
                case 'sprint':
                    foreach ($this->getServer()->wsClients as $id => $client) {
                        if ($client['sprint'] == $sprintId) {
                            $this->getServer()->wsSendJson($id, $result['data']);
                        }
                    }
                    break;
                case 'sprint_except_sender':
                    foreach ($this->getServer()->wsClients as $id => $client) {
                        if ($client['sprint'] == $sprintId && $id != $clientId) {
                            $this->getServer()->wsSendJson($id, $result['data']);
                        }
                    }
                    break;
                case 'all':
                    foreach ($this->getServer()->wsClients as $id => $client) {
                        $this->getServer()->wsSendJson($id, $result['data']);
                    }
                    break;
                default:
                    foreach ($this->getServer()->wsClients as $id => $client) {
                        if (in_array($client['user_data']['id'], $result['destination'])
                            && $client['sprint'] == $sprintId
                        ) {
                            $this->getServer()->wsSendJson($id, $result['data']);
                        }
                    }
                    break;
            }
        }
    }
}
