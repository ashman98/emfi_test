<?php
namespace services\getFromAmoCrm;

use services\AmoCrmConnectAbstract;

class GetUsersInfoService extends AmoCrmConnectAbstract
{
    private $userID;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param mixed $userID
     * @return GetUsersInfoService
     */
    public function setUserID($userID)
    {
        $this->userID = $userID;
        return $this;
    }

    public function getUserInfo()
    {
            $url = "https://$this->subdomain.amocrm.ru/api/v4/users/$this->userID";

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
            return $user['_embedded']['users'][0];
    }
}