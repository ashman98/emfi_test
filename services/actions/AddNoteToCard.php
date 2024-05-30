<?php
namespace services\actions;

use services\AmoCrmAuthTrite;

class AddNoteToCard
{
    use AmoCrmAuthTrite;

    private $entityType;
    private $entityID;
    private $noteText;

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
        $url = "https://$this->subdomain.amocrm.ru/api/v4/$this->entityType/$this->entityID/notes";
        $data = [
            [
                'entity_id' =>$this->entityID,
                'note_type' => 'common',
                'params' => [
                    'text' => $this->noteText
                ]
            ]
        ];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $this->accessToken",
            "Content-Type: application/json"
        ]);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($curl);
        curl_close($curl);

       $log = date('Y-m-d H:i:s').' '.$response;
       file_put_contents(dirname(dirname(__DIR__)) . '/var/logs/log.txt', $log . PHP_EOL, FILE_APPEND);

        return json_decode($response, true);
    }
}