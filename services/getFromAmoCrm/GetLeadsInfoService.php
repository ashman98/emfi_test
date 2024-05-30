<?php
namespace services\getFromAmoCrm;

use services\AmoCrmConnectAbstract;

class GetLeadsInfoService extends AmoCrmConnectAbstract
{
    private $leadsID;

    public function __construct()
    {
        parent::__construct();
    }


    /**
     * @param mixed $leadsID
     * @return GetLeadsInfoService
     */
    public function setLeadsID($leadsID)
    {
        $this->leadsID = $leadsID;
        return $this;
    }

    public function getLeadsInfo():array
    {
            $url = "https://$this->subdomain.amocrm.ru/api/v4/leads/$this->leadsID";

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

            $leads = json_decode($response, true);
            if (!empty($leads)){
                $leads = $leads['_embedded']['leads'][0];
            }

            return $leads??[];
    }
}