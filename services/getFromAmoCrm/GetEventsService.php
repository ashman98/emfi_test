<?php
namespace services\getFromAmoCrm;

use services\AmoCrmConnectAbstract;

class GetEventsService extends AmoCrmConnectAbstract
{
    private $filterParams  = [];

    public function __construct()
    {
        parent::__construct();
    }


    /**
     * @param array $filterParams
     * @return GetEventsService
     */
    public function setFilterParams($filterParams): GetEventsService
    {
        $this->filterParams = $filterParams;
        return $this;
    }

    public function getEvents():array
    {
            $url = "https://$this->subdomain.amocrm.ru/api/v4/events";
            $queryString = http_build_query($this->filterParams);

            $endpointUrl = "$url?$queryString";

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
            curl_setopt($curl, CURLOPT_URL, $endpointUrl);
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $this->accessToken",
                "Content-Type: application/json"
            ]);

            $response = curl_exec($curl);
            curl_close($curl);

            $events = json_decode($response, true);
            return $events??[];
    }
}