<?php
namespace services;

use PDO;
use PDOException;
use services\db\DBconnect;

class AmoCrmConnectAbstract
{
    private $clientId = 'edefe1b3-baf7-42af-899c-50621eb275b0';
    private $clientSecret = '4EzbMwzAE0Fwg0RuRL0r2Bocfu72PCYeECmGd8sSFHvJq36gyx32y1j5ZVRxq7z7';
    private $code = 'def502007d4bff016ea7273343a00d2dc536a52455c21324f82933f765be56703aeb02eeb5e395b45bbbeeaab9a2e407dd00364423dfd453d6234382a0cb8e6caa0e9f0a0a45f18f9e5fb0f8cfd92a74d851625790dcc85f67168e2f7d062835fa8b436cac6bf05c19d662dacde6a8a4e18b7e892081d327377ff0554c6b460a1468fecaaaf2cedbf73569889733b476d4b81cc6188ac5d71b1cf6697240a4d6119a482bec8cd8ad00f74331a7191180241333fcb69b02dd48c6de72ea9788973d7bcdd7f859f694daaf9eb44f698d0682ff5bf67afa3776a07db28302e04b73a25fe37ff98f99977e80f0efa175ff85fd72debb410e5b9852ff34a8a69cbd3165edb11ad95bfba2b678e8d7db29215945666d33eedaa6986922837ba50e54577b13c9b46d1f4517d98be159a75b54ff763be887e5cb45eb28e874b25ab91dd0af8e171c24256317966f70130f64fad9d96af8154726121498829d818331f989819e8610756cc22899e9ca5a575e353fce0de58e4613231ea4154554195369067efe38feb00df1b1a9baef80cb604f24767f0bc9d86cb3e8ecea9a5f9b331614e0be8d14d249d526d18884ee25dae4d9e7a32d5092def244cdb03c362bc67349d3f4c8fe038321a9ae63ed1ba2b4e3d782f62bef90370037cb028b1e5d89f48b6daef8293cc67a15c71aa6';
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

        if ($data['grant_type'] === 'refresh_token'){
            if ($code < 200 || $code > 204) {
                $this->authorize();
            }
        }

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
            $selectToken = $conn->prepare("SELECT * FROM tokens ORDER BY id DESC" );
            $selectToken->execute();
            $tokenAssoc = $selectToken->fetchAll(PDO::FETCH_ASSOC);

            if(!empty($tokenAssoc)){
                foreach ($tokenAssoc as $val){
                    $stmt= $conn->prepare("DELETE FROM users WHERE id=?");
                    $stmt->execute([$val['id']]);
                }
            }

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