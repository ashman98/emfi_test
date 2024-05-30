<?php
namespace services;

use PDO;
use PDOException;
use services\db\DBconnect;

class AmoCrmConnectAbstract
{
    private $clientId = 'edefe1b3-baf7-42af-899c-50621eb275b0';
    private $clientSecret = 'XY5vPveMNOdA6nurraTt8xbIFUoHLTRDUVkq1X9yenktOnPYgZlYMs9rFiXMY2QW';
    private $code = 'def50200c025323b3abd4ac473282dd3600e11de7ca291cad4775d21f9c54e21c0c208ee22b21ee6380b76f16891f0c7ce9ce99490ea8125d94be412d3cf93e7d0f3ce6efb2a8bc569fafc5466a621c93c76f18e510479423e1ce8f7d7e5bd0cd35ff7081ec3fd269077c8b97f511a3b63bf0ce90f15b23efbe693f7758e28aadeb69500941f1ad077c0b15caf1aaab1c4ed1537fa0e8a6aa6da17405cfd1b7c8d4c9983821c311470a3be01186bf82c2baa10039fa57600d55ad2d4392310a1a51f76ed4f276f278b49c8155d5f0a71e0df27d7aa1fc68e797cc68c43abd09b03b9afffedf5cab9c5faba1ac9f2f4876e1962ee57ea51653415b77bdd91f017cbca4d4e29d39a10984fbd6b4dacc3e86171147ff3c06fb3ba443d91b967eaa6299651dab6bbb1530dc31ca16a7e179b5719d75e67e1afe3f4586e7e44f5530b7211321c4d68136f32bbb030d63ef4358fc1aef353a79129312d307fd9293429ba97e10eaed90de9dd26c9302e736b0e55554c8e47271e05e77a2c357d191ff24854c084e0c894807324f4c8dbaa283a3c9807cdd7f06f0a381bdd4434438daa7720629302a80b62031022c787f18df6cf2ef1cedaf519b0fccb17e37d58ea4c6ab73a11be752f2d73471325dfddc60f943a888e356dbd57d03fb7c41b8b71bd993b3be5682e021bd1d34a2cb0';

    private $redirectUri = 'https://pbl.up.railway.app/';
    private $refreshToken = '';

    protected $subdomain = 'emfitestmailru';
    protected $accessToken = '';

    public function __construct()
    {
        $this->refreshToken = $this->loadRefreshToken();

        if (empty($this->refreshToken)) {
            $this->authorize();
        } else {
            $this->refreshToken();
        }
    }

    private function authorize()
    {
        $link = 'https://' . $this->subdomain . '.amocrm.ru/oauth2/access_token';

        $data = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'authorization_code',
            'code' => $this->code,
            'redirect_uri' => $this->redirectUri
        ];

        $response = $this->makeCurlRequest($link, $data);

        $this->handleResponse($response);
    }

    private function refreshToken()
    {
        $link = 'https://' . $this->subdomain . '.amocrm.ru/oauth2/access_token';

        $data = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->refreshToken,
            'redirect_uri' => $this->redirectUri
        ];

        $response = $this->makeCurlRequest($link, $data);

        $this->handleResponse($response);
    }

    private function makeCurlRequest($url, $data)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
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

//        try {
//            if ($code < 200 || $code > 204) {
//                throw new \Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
//            }
//        } catch (\Exception $e) {
//            die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
//        }

        return json_decode($out, true);
    }

    private function handleResponse($response)
    {
        if (isset($response['access_token'])) {
            $this->accessToken = $response['access_token'];
            $this->refreshToken = $response['refresh_token'];
            $this->saveRefreshToken($this->refreshToken);
            echo 'Authorization success';
        } else {
            echo('Ошибка: Invalid response from authorization server');
            die();
        }
    }

    private function saveRefreshToken($token)
    {
        $db = new DBconnect;
        $conn = $db->conn();
        $conn->beginTransaction();
        try {
            $insertToken = $conn->prepare("INSERT INTO tokens (token) VALUES (:token)");
            $insertToken->execute([':token' => $token]);
            echo "New token created successfully";
            $conn->commit();
        } catch(PDOException $e) {
            $conn->rollback();
            echo $sql . "<br>" . $e->getMessage();
        }
    }

    private function loadRefreshToken()
    {
        $db = new DBconnect;
        $conn = $db->conn();

        $token = $conn->prepare("SELECT * FROM tokens ORDER BY id DESC" );
        $token->execute();
        $tokenAssoc = $token->fetchAll(PDO::FETCH_ASSOC);

        if(isset($tokenAssoc[0])){
            return $tokenAssoc[0]['token'];
        }

        return '';
    }
}