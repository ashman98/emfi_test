<?php
namespace services\actions;

use services\actions\AddNoteToCard;
use services\actions\SaveLeads;
use services\AmoCrmAuthTrite;
use services\getFromAmoCrm\GetEventsService;
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
            $actionData = [
                'action_type' => 'add',
                'entity_id' => 0,
                'entity_type' => '',
                'rend_data' => [],
                'create_time' => '' ,
            ];
            $note_text = '';
            $events = [];
            if (isset($this->hookData['leads'])){
                $actionData['entity_type'] = 'lead';
                $note_text .= 'Название сделки';

//
                if (isset($this->hookData['leads']['add'])){
                    $actionData['entity_id'] = (int)$this->hookData['leads']['add'][0]['id'];
//                    $actionData['create_time'] = $this->hookData['leads']['add'][0]['created_at'];
                    $actionData['action_type'] = 'add';
                }else{
                    $actionData['entity_id'] = (int)$this->hookData['leads']['update'][0]['id'];
                    $actionData['action_type'] = 'update';
                }

//                $actionData['entity_id'] = 293515;


//                if (!empty($actionData['entity_id'])){
                    $getLeadsInfoService = new GetLeadsInfoService();
                    $leadsInfo = $getLeadsInfoService->setLeadsID((int)$actionData['entity_id'])->getLeadsInfo();

                    $get = new GetEventsService();
                    $events = $get->setFilterParams([
                            'filter[entity]' =>  $actionData['entity_type'],
                            'filter[entity_id][]' => $actionData['entity_id'],
                            'filter[created_at][from]' => $leadsInfo['updated_at'],
                            'filter[type]' => [
                                'lead_added',
                                'sale_field_changed',
                                'contact_added',
                                'name_field_changed',
                                'custom_field_value_changed',
                                'entity_responsible_changed'
                            ]
                        ])->getEvents();
//                    print_r($leadsInfo);

                    $getUsers = new GetUsersInfoService();
                    $responsibleUser = $getUsers->setUserID((int)$leadsInfo['responsible_user_id'])->getUserInfo();
                     $leadsInfo['responsible_user_name'] =   $responsibleUser['name'];


//                    if (!empty($leadsInfo)){
//                        $saveLeads = new SaveLeads();
//                        $saveLeads->setData($leadsInfo)->addLeads();
//                        $actionData['rend_data'] = $saveLeads->getRendData();
//                    }
//                }
            }
//            elseif (isset($this->hookData['contacts'])){
//                $actionData['entity_type'] = 'contact';
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

//            if (empty($actionData['rend_data'])) {
//                print_r('error')
//                return ['error' => 'rend_date'];
//            }

            if ($actionData['action_type'] === 'add') {
                $note_text .= ": " . $leadsInfo['name']
                    . "\nОтветственный: " . $leadsInfo['responsible_user_name']
                    . "\nВремя добавления карточки: " . date('Y-m-d H:i:s', $leadsInfo['created_at']);
            } else{
                    $changes = '';
                print_r($events);
                    if (isset($events['_embedded']['events'][0])){
//                        print_r($events['_embedded']['events']);
//                        print_r($events['_embedded']['events'][0]);
                        foreach ($events['_embedded']['events'][0]['value_after'] as $key => $evernt) {
                            print_r($evernt);
                            foreach ($evernt as $l){
//                                print_r($l);
                                foreach ($l as $k => $v){
//                                    if ($v === 'name'){
                                        $changes .= $k."=>".$v." ";
//                                    }
//                                    $newValue = $event['value_after'][0]['name_field_value']['name'];
                                }
                            }

                        }
                    }
                $note_text .= ": "
                    . $leadsInfo['name']
                    . "\nИзмененные поля: " . $changes
                    . "\nВремя изменения карточки: " . date('Y-m-d H:i:s', $leadsInfo['updated_at']);
            }


            $addNoteToCard = new AddNoteToCard;
            $addNoteToCard
                ->setEntityType($actionData['entity_type'])
                ->setEntityID($actionData['entity_id'])
                ->setNoteText($note_text)
                ->addNoteToCard();
        }

        return [];
    }


}