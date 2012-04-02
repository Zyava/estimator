<?php
class Sprint
{
    /**
     * Get list of all sprints
     *
     * @return array
     */
    public function getSprintList()
    {
        $pdo = Db::getInstance()->pdo;
        $stmt = $pdo->query("select * from sprints order by id desc");
        $sprintList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array('data' => $sprintList);
    }
}
