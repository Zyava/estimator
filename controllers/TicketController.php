<?php
class TicketController
{
    /**
     * Get ticked list to user already logged
     *
     * @param int $sprintId
     * @param array $data
     * @param array $client
     * @return array
     */
    public function getTicketList($sprintId, $data, $client)
    {
        $pdo = Db::getInstance()->pdo;
        $stmt = $pdo->query("
            SELECT CONCAT('card',id) as id, title, description, estimate, coord_top, coord_left, status,
                CONCAT('column', estimate) AS container
            FROM tickets
            WHERE sprint_id =".intval($sprintId));
        $ticketsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $i = 0;
        foreach ($ticketsData as $key => $ticket) {
            if ($ticket['container'] == 'column0' ) {
                $i++;
                $ticketsData[$key]['container'] .= str_repeat('0', $i%3);
            }
        }

        $foreachData = $ticketsData;
        foreach ($foreachData as $ticket) {
            $ticket['container'] = 'drag_container';
            $ticket['draggable'] = true;
            $ticket['id'] = str_replace('card', 'task', $ticket['id']);
            $ticket['position'] = array(
                'top' => $ticket['coord_top'],
                'left' => $ticket['coord_left']
            );
            $ticketsData[] = $ticket;
        }

        return array(
            'destination' => 'sender',
            'data' => array(
                'class' => 'Estimator',
                'method' => 'setState',
                'data'  => $ticketsData
            ));
    }

    /**
     * Set estimate to Ticket and send new values to all users
     *
     * @param int $sprintId
     * @param array $data
     * @param array $client
     * @return array
     */
    public function onEstimate($sprintId, $data, $client)
    {
        $estimate = str_replace('column','',$data['container']);
        $id = str_replace('card','',$data['id']);

        $pdo = Db::getInstance()->pdo;
        $stmt = $pdo->prepare(
            'UPDATE tickets
             SET estimate = :estimate
             WHERE id = :id');
        $stmt->bindValue(':estimate', $estimate);
        $stmt->bindValue(':id', $id);
        $stmt->execute();

        $log = UserLogs::getInstance($sprintId);
        $log->save($client['user_data']['id'], "Ticket " . $data['title'] . " estimated in " . $estimate);

        return array(
            'destination' => 'sprint_except_sender',
            'data' => array(
                'class' => 'Estimator',
                'method' => 'setState',
                'data'  => $data
            ));
    }

    /**
     * Create new ticket
     *
     * @param int $sprintId
     * @param array $list
     * @param array $client
     * @return array
     */
    public function onCreate($sprintId, $list, $client)
    {
        foreach ($list as &$data) {
            $estimate = str_replace('column', '', $data['container']);

            $pdo = Db::getInstance()->pdo;
            if ($estimate != $data['container']) {
                $stmt = $pdo->prepare('insert into tickets(title, description, estimate,
                    sprint_id, coord_top, coord_left) values
                    (:title, :description, :estimate, :sprint, :top, :left)');
                $stmt->bindValue(':title', $data['title']);
                $stmt->bindValue(':description', $data['description']);
                $stmt->bindValue(':estimate', $estimate);
                $stmt->bindValue(':sprint', $sprintId);
                $stmt->bindValue(':top', $data['position']['top']);
                $stmt->bindValue(':left', $data['position']['left']);
                $stmt->execute();

                unset($data['position']);

                $data['id'] = 'card' . $pdo->lastInsertId();
            } else {
                $data['id'] = 'task' . $pdo->lastInsertId();
            }
        }

        $log = UserLogs::getInstance($sprintId);
        $log->save($client['user_data']['id'], "Ticket " . $data['title'] . " created.");

        return array(
            'destination' => 'sprint',
            'data' => array(
                'class' => 'Estimator',
                'method' => 'setState',
                'data'  => $list
            )
        );
    }

    /**
     * Change ticket block position
     *
     * @param int $sprintId
     * @param array $data
     * @param array $client
     * @return array
     */
    public function onChangePosition($sprintId, $data, $client)
    {
        return array(
            'destination' => 'sprint_except_sender',
            'data' => array(
                'class' => 'Estimator',
                'method' => 'setState',
                'data'  => $data
            ));
    }

    /**
     * Save ticket position
     *
     * @param int $sprintId
     * @param array $data
     * @param array $client
     * @return array
     */
    public function onSavePosition($sprintId, $data, $client)
    {
        $id = str_replace('task', '', $data['id']);
        $left = 20 * round($data['position']['left'] / 20);
        $top = 20 * round($data['position']['top'] / 20);

        $pdo = Db::getInstance()->pdo;
        $stmt = $pdo->prepare('update tickets set coord_left = :cleft, coord_top = :ctop where id = :id');
        $stmt->bindValue(':cleft', $left);
        $stmt->bindValue(':ctop', $top);
        $stmt->bindValue(':id', $id);
        $stmt->execute();

        $log = UserLogs::getInstance($sprintId);
        $log->save($client['user_data']['id'], "Moved ticket " . $data['title'] . " to top: $top, left: $left");

        return array(
            'destination' => 'sprint_except_sender',
            'data' => array(
                'class' => 'Estimator',
                'method' => 'setState',
                'data'  => $data
            ));
    }

    /**
     * Update ticket
     *
     * @param int $sprintId
     * @param array $data
     * @param array $client
     * @return array
     */
    public function onUpdate($sprintId, $data, $client)
    {
        $id = str_replace(array('task', 'card'), '', $data['id']);

        $pdo = Db::getInstance()->pdo;
        $stmt = $pdo->prepare('
            UPDATE tickets
            SET title = :title,
                description = :description
            WHERE id = :id');
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':title', $data['title']);
        $stmt->bindValue(':description', $data['description']);
        $stmt->execute();

        $log = UserLogs::getInstance($sprintId);
        $log->save($client['user_data']['id'], "Ticket " . $data['title'] . " updated");

        $data['id'] = 'card' . $id;
        $card = array(
            'id' => 'task' . $id,
            'title' => $data['title'],
            'description' => $data['description']
        );

        return array(
            'destination' => 'sprint',
            'data' => array(
                'class' => 'Estimator',
                'method' => 'setState',
                'data'  => array($data, $card)
            )
        );
    }

    /**
     * Delete ticket
     *
     * @param int $sprintId
     * @param array $data
     * @param array $client
     * @return array
     */
    public function onDelete($sprintId, $data, $client)
    {
        $id = str_replace(array('task', 'card'), '', $data['id']);

        $pdo = Db::getInstance()->pdo;
        $stmt = $pdo->prepare('
            DELETE FROM tickets
            WHERE id = :id');
        $stmt->bindValue(':id', $id);
        $stmt->execute();

        $log = UserLogs::getInstance($sprintId);
        $log->save($client['user_data']['id'], "Ticket " . $data['title'] . " deleted");

        return array(
            'destination' => 'sprint',
            'data' => array(
                'class' => 'Estimator',
                'method' => 'deleteTask',
                'data'  => $id
            )
        );
    }

    public function onStatusChange($sprintId, $data, $client)
    {
        $pdo = Db::getInstance()->pdo;
        $stmt = $pdo->prepare('
            UPDATE tickets
            SET status = :status
            WHERE id = :id');
        $stmt->bindValue(':id', $data['id']);
        $stmt->bindValue(':status', $data['status']);
        $stmt->execute();

        $stmt = $pdo->query("
            SELECT id, status
            FROM tickets
            WHERE sprint_id =" . intval($sprintId));
        $ticketsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array(
            'destination' => 'sprint',
            'data' => array(
                'class' => 'CalcController',
                'method' => 'onStatusChange',
                'data'  => $ticketsData
            ));
    }
}
