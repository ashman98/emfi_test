<?php
namespace services\actions;

use services\AmoCrmConnectAbstract;

class AddNoteToCard extends AmoCrmConnectAbstract
{
    private $entityType;
    private $entityID;
    private $noteText;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param mixed $entityType
     * @return AddNoteToCard
     */
    public function setEntityType($entityType)
    {
        $this->entityType = $entityType;
        return $this;
    }

    /**
     * @param mixed $entityID
     * @return AddNoteToCard
     */
    public function setEntityID($entityID)
    {
        $this->entityID = $entityID;
        return $this;
    }

    /**
     * @param mixed $noteText
     * @return AddNoteToCard
     */
    public function setNoteText($noteText)
    {
        $this->noteText = $noteText;
        return $this;
    }

   public function addNoteToCard() {
        $url = "https://$this->subdomain.amocrm.ru/api/v4/$this->entityType".'s'."/$this->entityID/notes";
        $data = [
            [
                'entity_id' =>$this->entityID,
                'note_type' => 'common',
                'params' => [
                    'text' => $this->noteText
                ]
            ]
        ];

       $curl = curl_init(); //Сохраняем дескриптор сеанса cURL
       curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
       curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
       curl_setopt($curl,CURLOPT_URL, $url);
       curl_setopt($curl,CURLOPT_HTTPHEADER, [
           "Authorization: Bearer $this->accessToken",
       ]);
       curl_setopt($curl,CURLOPT_HEADER, false);
       curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
       curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
       curl_setopt($curl, CURLOPT_POST, true);
       curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
       $out = curl_exec($curl);
       $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
       curl_close($curl);
       $code = (int)$code;
       $errors = [
           400 => 'Bad request',
           401 => 'Unauthorized',
           403 => 'Forbidden',
           404 => 'Not found',
           500 => 'Internal server error',
           502 => 'Bad gateway',
           503 => 'Service unavailable',
       ];

       try
       {
           if ($code < 200 || $code > 204) {
               echo ($errors[$code] ?? 'Undefined error'. $code);
           }
       } catch(\Exception $e)
       {
           die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
       }
    }
}