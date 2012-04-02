<?php
class SprintController
{
    /**
     * Change sprint
     *
     * @param int $sprintId
     * @param array $data
     * @param array $client
     * @param int $clientId
     * @return array
     */
    public function onChangeSprint($sprintId, $data, $client, $clientId)
    {
        if (isset($data['new_sprint_id']) && intval($data['new_sprint_id']) > 0) {
            $newSprintId = intval($data['new_sprint_id']);
            $server = Dispatcher::getInstance()->getServer();
            $server->wsClients[$clientId]['sprint'] = $newSprintId;

            $log = UserLogs::getInstance($newSprintId);
            $log->save($client['user_data']['id'], "User has logged to sprint.");

            Dispatcher::getInstance()->sendActiveUsers('sprint');

            return array(
                'destination' => 'sender',
                'data' => array(
                    'class' => 'SprintController',
                    'method' => 'onChangeSprint',
                    'data'  => array(
                        'refresh_page' => 1
                    ),
                )
            );
        }

        return array();
    }
}
