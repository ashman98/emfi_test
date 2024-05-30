<?php
namespace services\getFromAmoCrm;

use services\AmoCrmConnectAbstract;

class GetContactInfoService extends AmoCrmConnectAbstract
{
    private $contactID;

    public function __construct()
    {
        parent::__construct();
    }


    /**
     * @param mixed $contactID
     * @return GetContactInfoService
     */
    public function setContactID($contactID)
    {
        $this->contactID = $contactID;
        return $this;
    }

    public function getContactInfo():array
    {
            $url = "https://$this->subdomain.amocrm.ru/api/v4/contacts/$this->contactID";

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $this->accessToken",
                "Content-Type: application/json"
            ]);

            $response = curl_exec($curl);
            curl_close($curl);

            $user = json_decode($response, true);
            return $user;
    }
}