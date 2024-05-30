<?php
namespace services\actions;

use services\actions\AddNoteToCard;
use services\actions\SaveLeads;
use services\AmoCrmAuthTrite;
use services\getFromAmoCrm\GetLeadsInfoService;
use services\getFromAmoCrm\GetUsersInfoService;

class HandleWebhook
{
    use AmoCrmAuthTrite;

    private $hookData = [];

    public function setHookData(array $hookData): HandleWebhook
    {
        $this->hookData = $hookData;
        return $this;
    }

    public function handle()
    {
        if (isset($this->hookData['contacts']) || isset($this->hookData['leads'])) {

//            $log = date('Y-m-d H:i:s').' start';
//            file_put_contents(dirname(dirname(__DIR__)) . '/var/logs/log.txt', $log . PHP_EOL, FILE_APPEND);

            $actionData = [
                'action_type' => 'add',
                'entity_id' => 0,
                'entity_type' => '',
                'rend_data' => []
            ];
            $note_text = '';

//            $log = json_encode($this->hookData);
//            file_put_contents(__DIR__ . '/var/logs/log.txt', $log . PHP_EOL, FILE_APPEND);
            if (isset($this->hookData['leads'])){
                $actionData['entity_type'] = 'leads';
                $note_text .= 'Название сделки';

                if (isset($this->hookData['leads']['add'])){
                    $actionData['entity_id'] = (int)$this->hookData['leads']['add'][0]['id'];
                    $actionData['action_type'] = 'add';
                }elseif (isset($this->hookData['leads']['update'])){
                    $actionData['entity_id'] = (int)$this->hookData['leads']['update'][0]['id'];
                    $actionData['action_type'] = 'update';
                }

                if (!empty($actionData['entity_id'])){
                    $getLeadsInfoService = new GetLeadsInfoService();
                    $leadsInfo = $getLeadsInfoService->setLeadsID($actionData['entity_id'])->getLeadsInfo();

                    if (!empty($leadsInfo)){
                        $saveLeads = new SaveLeads();
                        $saveLeads->setData($leadsInfo)->addLeads();
                        $actionData['rend_data'] = $saveLeads->getRendData();
                    }
                }



            }
//            elseif (isset($this->hookData['contacts'])){
//                $actionData['entity_type'] = 'contacts';
//                $note_text .= 'Название контакта';
//
//                if (isset($this->hookData['contacts']['add'])){
//                    $actionData = $this->hookData['contacts']['add'][0];
//                    $action = 'add';
//                }elseif (isset($this->hookData['contacts']['update'])){
//                    $actionData = $this->hookData['contacts']['update'][0];
//                    $action = 'update';
//                }
//            }

            if (empty($actionData['rend_data'])) {
                return ['error' => 'rend_date'];
            }else{
                return $actionData['rend_data'];
            }


            if ($actionData['action_type'] === 'add') {
                $note_text .= ": " . $actionData['rend_data']['name']
                    . "\nОтветственный: " . $actionData['rend_data']['responsible_user_name']
                    . "\nВремя добавления карточки : " . date('Y-m-d H:i:s', $actionData['rend_data']['created_at']);
            }
//            elseif ($actionData['action_type'] == 'update') {
//    //                return ['name' => $this->hookData];
//    //                $changes = '';
//    //                foreach ($this->hookData['changes'] as $field => $newValue) {
//    //                    $changes .= "$field: $newValue\n";
//    //                }
//                $note_text .= ": "
//                    . $actionData['rend_data']['name']. "\nChanges:\n" . ' $changes '
//                    . "\nВремя изменения карточки: " . date('Y-m-d H:i:s', $actionData['rend_data']['update_at']);
//            }


            $addNoteToCard = new AddNoteToCard;
            $addNoteToCard->setAccessToken($this->accessToken);
            $addNoteToCard->setSubdomain($this->subdomain);
            return $addNoteToCard
                ->setEntityType($actionData['entity_type'])
                ->setEntityID($actionData['entity_id'])
                ->setNoteText($note_text)
                ->addNoteToCard();
        }

        return [];
    }


}