<?php
namespace services\getFromAmoCrm;

use services\AmoCrmConnectAbstract;


class GetFieldsGroupsService extends AmoCrmConnectAbstract
{
    private $fieldID;

    private $fieldsGroupID;

    public function __construct()
    {
        parent::__construct();
    }


    /**
     * @param mixed $fieldsGroupID
     * @return GetFieldsGroupsService
     */
    public function setFieldsGroupID($fieldsGroupID)
    {
        $this->fieldsGroupID = $fieldsGroupID;
        return $this;
    }

    /**
     * @param mixed $fieldID
     * @return GetFieldsGroupsService
     */
    public function setFieldID($fieldID)
    {
        $this->fieldID = $fieldID;
        return $this;
    }


    public function getFieldsGroups():array
    {
            if (empty($this->fieldsGroupID)){
                echo 'error fildsGroupID is missing';
                die();
            }

            $url = "https://$this->subdomain.amocrm.ru/api/v4/contacts/custom_fields/groups/$this->fieldsGroupID";

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

            $fieldGroups = json_decode($response, true);
            return $fieldGroups;
    }

    public function getField():array
    {
        if (empty($this->fieldID)){
            echo 'error fieldID is missing';
            die();
        }

        $url = "https://$this->subdomain.amocrm.ru/api/v4/contacts/custom_fields/$this->fieldID";

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

        $fieldData = json_decode($response, true);
        return $fieldData;
    }
}