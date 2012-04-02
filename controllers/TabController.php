<?php
class TabController
{
    /**
     * @param int $sprintId
     * @param array $data
     * @param array $client
     * @return array
     */
    public function onChangeCurrentTab($sprintId, $data, $client)
    {
        $pdo = Db::getInstance()->pdo;
        $stmt = $pdo->prepare('Insert into session_data (sprint_id, data_key, data_value)
            values (:sprint, :data_key, :data_value )
            on duplicate key update data_value = :data_value');
        $stmt->bindValue(':sprint', $sprintId);
        $stmt->bindValue(':data_key', 'current_tab');
        $stmt->bindValue(':data_value', $data['tab_index']);
        $stmt->execute();

        $log = UserLogs::getInstance($sprintId);
        $log->save($client['user_data']['id'], "Changed tab index to " . $data['tab_index']);

        return array(
            'destination' => 'sprint_except_sender',
            'data' => array(
                'class' => 'TabController',
                'method' => 'onChangeCurrentTab',
                'data'  => array(
                    'tab_index' => $data['tab_index']
                ),
            ));
    }
}
