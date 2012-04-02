<?php
class Session
{
    /**
     * Get session params
     *
     * @param int $sprintId
     * @return array
     */
    public function getSessionParams($sprintId)
    {
        $pdo = Db::getInstance()->pdo;
        $stmt = $pdo->query("select * from session_data where sprint_id = " . intval($sprintId));
        $paramsList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resultArray = array();
        foreach ($paramsList as $param) {
            $resultArray[$param['data_key']] = $param['data_value'];
        }

        return array('data' => $resultArray);
    }
}
