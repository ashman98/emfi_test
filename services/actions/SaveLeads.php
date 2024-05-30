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
    private $rendData = [];


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

    public function getRendData(): array
    {
        return $this->rendData;
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
            $this->generateRendData($leads[0]);
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
//            $lastInsertLeadsId = $this->pdo->lastInsertId();

//            if (!empty($this->data['_embedded']['tags'])){
//                foreach ($this->data['_embedded']['tags'] as $tag){
//                            $insertTags = $this->pdo->prepare("
//                        INSERT INTO tags (external_id, name, leads_id,color)
//                        VALUES (:external_id, :name, :leads_id,:color)
//                    ");
//                    $insertTags->execute(array(
//                        ':external_id' => $tag['id'],
//                        ':name' => $tag['name'],
//                        ':leads_id' =>  (int)$lastInsertLeadsId,
//                        ':color' => 'color'
//                    ));
//                }
//            }


            $this->pdo->commit();

        } catch(PDOException $e) {
            $this->pdo->rollback();
            echo ( "Error!: " . $e->getMessage() . "</br>");
        }
    }

    private function generateRendData($leads)
    {
        $rendDataKeys = [
            'name' => "Название",
            'price' => "Бюджет",
            'responsible_user_name' => 'Ответственный',
            'created_at' => 'Время добавления карточки',
            'updated_at' => 'Время изменения карточки'
        ];

        if (!empty($this->data['responsible_user_id'])){
            $getUsers = new GetUsersInfoService();
            $responsibleUser = $getUsers->setUserID((int)$this->data['responsible_user_id'])->getUserInfo();
            $this->rendData['responsible_user_name'] = ['val' => $responsibleUser['name'],'is_changed' => 0, 'rus' => $rendDataKeys['responsible_user_name']];
        }

        foreach ($this->data as $key => $value){
            if(array_key_exists($key, $rendDataKeys)){
                echo '<br>'.$key.'<br>';
             if (!empty($leads)){
                    if (array_key_exists($key, $leads) && $leads[$key] != $value){
                        if ($key === 'responsible_user_id'){
                            $this->rendData['responsible_user_name']['is_changed'] = 1;
                            continue;
                        }

                        $this->rendData[$key] = ['val' => $value, 'is_changed' => 1, 'rus' => $rendDataKeys[$key]];
                        continue;
                    }
                }


                $this->rendData[$key] = ['val' => $value,'is_changed' => 0, 'rus' => $rendDataKeys[$key]];
            }
        }
    }



}