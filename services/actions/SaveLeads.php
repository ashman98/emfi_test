<?php
namespace services\actions;

use PDO;
use PDOException;
use services\db\DBconnect;
use services\getFromAmoCrm\GetUsersInfoService;

class SaveLeads
{
    private $pdo = null;
    private $data = [];
    private $changesValues = [];


    public function __construct()
    {
        $db = new DBconnect();
        $this->pdo = $db->conn();
    }

    public function setData(array $data): SaveLeads
    {
        $this->data = $data;
        return $this;
    }

    public function getChangesValues(): array
    {
        return $this->changesValues;
    }

    public function addLeads()
    {
        $selectLeads = $this->pdo
            ->prepare("
                    SELECT * FROM leads WHERE 
                        external_id = :external_id ORDER BY updated_at DESC" );
        $selectLeads->execute(array(':external_id' => (int)$this->data['id']));

        $leads = $selectLeads->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($leads)){
            $this->detectChanges($leads[0]);
        }

        try {
            $this->pdo->beginTransaction();
            $insertLeads = $this->pdo->prepare(
                "INSERT INTO leads (external_id,name,price,responsible_user_id,group_id, status_id, pipeline_id,loss_reason_id,created_by,updated_by,closed_at,closest_task_at,is_deleted,score,account_id,created_at,updated_at) 
                    VALUES (:external_id,:name, :price, :responsible_user_id, :group_id,:status_id, :pipeline_id,  :loss_reason_id, :created_by,:updated_by,:closed_at,:closest_task_at,:is_deleted,:score,:account_id,:created_at,:updated_at)"
            );
            $insertLeads->execute(array(
                ':external_id' =>  (int)$this->data['id'],
                ':name' => $this->data['name'],
                ':price' => $this->data['price'],
                ':responsible_user_id' => (int)$this->data['responsible_user_id'],
                ':group_id' => (int)$this->data['group_id'],
                ':status_id' => (int)$this->data['status_id'],
                ':pipeline_id' => (int)$this->data['pipeline_id'],
                ':loss_reason_id' => (int)$this->data['loss_reason_id'],
                ':created_by' => (int)$this->data['created_by'],
                ':updated_by' => (int)$this->data['updated_by'],
                ':closed_at' => (int)$this->data['closed_at'],
                ':closest_task_at' => (int)$this->data['closest_task_at'],
                ':is_deleted' => (int)$this->data['closed_at'],
                ':score' => (int)$this->data['score'],
                ':account_id' => (int)$this->data['account_id'],
                ':created_at' => date('Y-m-d H:i:s', $this->data['created_at']),
                ':updated_at' => date('Y-m-d H:i:s', $this->data['updated_at']),
            ));
            $lastInsertLeadsId = $this->pdo->lastInsertId();

            if (!empty($this->data['_embedded']['tags'])){
                foreach ($this->data['_embedded']['tags'] as $tag){
                            $insertTags = $this->pdo->prepare("
                        INSERT INTO tags (external_id, name, leads_id) 
                        VALUES (:external_id, :name, :leads_id)
                    ");
                    $insertTags->execute(array(
                        ':external_id' => $tag['id'],
                        ':name' => $tag['name'],
                        ':leads_id' =>  (int)$lastInsertLeadsId,
                    ));
                }
            }


            $this->pdo->commit();

        } catch(PDOException $e) {
            $this->pdo->rollback();
            echo ( "Error!: " . $e->getMessage() . "</br>");
        }
    }

    private function detectChanges($leads)
    {
        $rendDataKeys = [
            'name',
            'price',
            'responsible_user_name',
            'created_at',
            'updated_at'
        ];

        foreach ($this->data as $key => $value){
            if (isset($leads[$key]) && array_key_exists($key, $rendDataKeys) && $leads[$key] != $value){
                $log = date('Y-m-d H:i:s').' '.$leads[$key] . ' !== ' . $value;
                file_put_contents(dirname(dirname(__DIR__)) . '/var/logs/log.txt', $log . PHP_EOL, FILE_APPEND);

                if ($leads['responsible_user_id'] === $value['responsible_user_id'] && $key === 'responsible_user_id'){
                    $getUsers = new GetUsersInfoService();
                    $responsibleUser = $getUsers->setUserID($value['responsible_user_id'])->getUserInfo();
                    $this->changesValues['responsible_user_name'] = $responsibleUser['name'];
                }

                $this->changesValues[$key] = $value;
             }
        }
    }



}