<?php
namespace services;

use PDO;
use PDOException;
use services\db\DBconnect;

class AmoCrmConnectAbstract
{
    private $clientId = 'edefe1b3-baf7-42af-899c-50621eb275b0';
    private $clientSecret = 'XY5vPveMNOdA6nurraTt8xbIFUoHLTRDUVkq1X9yenktOnPYgZlYMs9rFiXMY2QW';
    private $code = 'def502005bdad70b7bca26d0783152d6baf022cf660711ebc5e5b8294c25407188306c79f2093d541d5bbb615296ef22229f96004b30fddfd9d4f4174865238b022333d76ea147a2baef093cdd86a7ed8de2238fa6617e61d0f0d64b56b4d16eb9f846166ec7a99844d13a8ee6ddc01c1c4ec3f40e0bff76295bcca7db9728bc4deba72238622b44a2f309800d0298afa67dbea2cfd6af96322c43b7bf992263b1201b4d4f37af2b5fb309a224ffe2cea9f02d495ec3a6c620aef1b0d60907bb0c011de04a07596e5cf7a96988086c47845d377c99c3be00a48288195862bab90e16e98506d382176deedfca76f91217cdc1200a8216df747dcc2a3a3f92fe94bdf735518f105f25803a50c9c92fdc2bf90dc53b88d3e2b9e35a9251194bab228adc849c2812349a71bba1655a54356e7ad83ac107e883fd4fbea1d33ef7274ed508c6f9fd0ec4f78e1436d30e86dd6f280e69c15272cfd521a7fa1bb97e1a66e32589d14cfbe7e91869f938dc396d1bfb5d349e7aa17e651e1598d72299443eca72da261cba0a97bebc3a9a600fa3612335dfaf8045b9a716cffc6ae099fb3cae133552ac2566a8c79feea30f1afc281f9ec84ad56eb7e26eba20942071dfc4e7483b3840f3d0563b58590529bdefb57c307444c925f839885d6afe4d458ab25e8a455c9dad3f83ba6550';

    private $redirectUri = 'https://pbl.up.railway.app';
    private $refreshToken = '';

    protected $subdomain = 'emfitestmailru';
    protected $accessToken = '';

    public function __construct()
    {
         $this->loadRefreshToken();

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
            $this->refreshToken = $tokenAssoc[0]['token'];
        }
    }
}