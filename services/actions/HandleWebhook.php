<?php
namespace services\actions;

use services\actions\AddNoteToCard;
use services\actions\SaveLeads;
use services\AmoCrmAuthTrite;
use services\getFromAmoCrm\GetContactInfoService;
use services\getFromAmoCrm\GetEventsService;
use services\getFromAmoCrm\GetFieldsGroupsService;
use services\getFromAmoCrm\GetLeadsInfoService;
use services\getFromAmoCrm\GetUsersInfoService;

class HandleWebhook
{
    use AmoCrmAuthTrite;

    private $lang = [
        'name' => 'Название',
        'sale' => 'Бюджет'
    ];

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
                'responsible_user_id' => 0
            ];

            $type =[];
            $note_text = '';
            if (isset($this->hookData['leads'])){
                $actionData['entity_type'] = 'lead';
                $note_text .= 'Название сделки';

                if (isset($this->hookData['leads']['add'])){
                    $actionData['entity_id'] = (int)$this->hookData['leads']['add'][0]['id'];
                    $actionData['action_type'] = 'add';
                    $actionData['create_time'] =  $this->hookData['leads']['add'][0]['created_at'];
                    $type[] = 'lead_added';
                }else{
                    $actionData['entity_id'] = (int)$this->hookData['leads']['update'][0]['id'];
                    $actionData['action_type'] = 'update';
                    $actionData['create_time'] =  $this->hookData['leads']['add'][0]['updated_at'];
                    $type[] = 'sale_field_changed';
                }
                $getLeadsInfoService = new GetLeadsInfoService();
                $leadsInfo = $getLeadsInfoService->setLeadsID((int)$actionData['entity_id'])->getLeadsInfo();
                $actionData['rend_data']['name'] = $leadsInfo['name'];
                $actionData['responsible_user_id'] = $leadsInfo['responsible_user_id'];
            }
            elseif (isset($this->hookData['contacts'])){
                $actionData['entity_type'] = 'contact';
                $note_text .= 'Название контакта';

                if (isset($this->hookData['contacts']['add'])){
                    $actionData['entity_id'] = (int)$this->hookData['contacts']['add'][0]['id'];
                    $actionData['action_type'] = 'add';
                    $actionData['create_time'] =  $this->hookData['contacts']['add'][0]['created_at'];
                    $type[] = 'contact_added';
                }else{
                    $actionData['entity_id'] = (int)$this->hookData['contacts']['update'][0]['id'];
                    $actionData['action_type'] = 'update';
                    $actionData['create_time'] =  $this->hookData['contacts']['add'][0]['updated_at'];
                    $type[] = 'custom_field_value_changed';
                }
                $getContactInfoService = new GetContactInfoService();
                $contactInfo = $getContactInfoService->setContactID((int)$actionData['entity_id'])->getContactInfo();
                $actionData['rend_data']['name'] = $contactInfo['name'];
                $actionData['responsible_user_id'] = $contactInfo['responsible_user_id'];
            }

            $getUsers = new GetUsersInfoService();
            $responsibleUser = $getUsers->setUserID((int)$actionData['responsible_user_id'])->getUserInfo();
            $actionData['rend_data']['responsible_user_name'] = $responsibleUser['name'];

            $get = new GetEventsService();
            $events = $get->setFilterParams([
                'filter[entity]' =>  $actionData['entity_type'],
                'filter[entity_id][]' => $actionData['entity_id'],
                'filter[created_at][from]' => $actionData['create_time'],
                'filter[type]' => array_merge([
                    'name_field_changed',
                    'entity_responsible_changed'
                ], $type)
            ])->getEvents();
            if (empty($events)){
                return;
            }

            if ($actionData['action_type'] === 'add') {
                $note_text .= ": " . $actionData['rend_data']['name']
                    . "\nОтветственный: " . $actionData['rend_data']['responsible_user_name']
                    . "\nВремя добавления карточки: " . date('Y-m-d H:i:s', $actionData['created_time']);
            } else{
                    $changes = '';
                    if (isset($events['_embedded']['events'][0])){
                        foreach ($events['_embedded']['events'][0]['value_after'] as $key => $evernt) {
                            print_r($evernt);
                            foreach ($evernt as $l){
                                if($actionData['action_type'] === 'add' && $actionData['entity_type'] === 'contact'){
                                    foreach ($l as $k => $v){
                                            $changes .= $k."=>".$v." ";
                                        }
                                }else{
                                    $getFieldsGroupsService = new GetFieldsGroupsService();
                                    $filedData = $getFieldsGroupsService->setFieldID((int)$l['field_id'])->getField();
                                    $changes .= json_encode($filedData);
                                }
                            }

                        }
                    }
                $note_text .= ": "
                    . $actionData['rend_data']['name']
                    . "\nИзмененные поля: " . $changes
                    . "\nВремя изменения карточки: " . date('Y-m-d H:i:s', $actionData['created_time']);
            }


            $addNoteToCard = new AddNoteToCard;
            $addNoteToCard
                ->setEntityType($actionData['entity_type'])
                ->setEntityID($actionData['entity_id'])
                ->setNoteText($note_text)
                ->addNoteToCard();
        }

        return;
    }


}