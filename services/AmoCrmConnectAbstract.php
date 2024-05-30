<?php
namespace services;

use PDO;
use PDOException;
use services\db\DBconnect;

class AmoCrmConnectAbstract
{
    private $clientId = 'edefe1b3-baf7-42af-899c-50621eb275b0';
    private $clientSecret = 'Bh4L837MlhP2OjmqblTdJjyNWzpoRcFkzyq69XcrI71TAEUdnGrcchH8NLyga1G0';
    private $code ='def5020001fdb74b2afe7aa72f27d30be546f15332d42b913fb47b956c86b8ba2608d19d8e8e711019578671be059bcd24fb12635f2925af51c1c2ac3d9e6c719e0410949373cfa7b10aa1d4896de5321439aac18510d6829bfb1022ccb64d10b3eb308532553f07d1b69e0493e27a28f0b7eaa1dcd36830889f5690c1b1c8cf49843e0c53b80d7f5fbbaa94751bfead4f570f55fd0b6b1bcb26cf0c1faf95cbf23f9bbdb6bde8bffc0a5542fa7ecb83ee8cc7b2ba3788f5dfc002447d3043012dc6026c38e2d2fa27feb7a8aef3df1146d5972607dee753096ad6e1f606e393244dc3c05c5bab0e075e24799b9a8e4cc519954b7750a0b783719825dc1b3363c329bd116a85e2bc02e6b388f7b5133abbb5d7f7f2a73ba67d1dad76e198cb88e6a65b3e384497677dfd742af97699ef3365e1f87fe8cf3a5feb7e4f55a75f7d28dc639b644513933c8b6cdb3226830081ed3047728b441e591341db585e78dae34b6521947964cb55066e470045399fb2c3f5be3bde1843679c0749d4115680be1015a11b5b705acb9006ef99df91b36681633732aa57b055ca4217da9e900d264af1b0ae21a119e5791bf047ecacdc92e7fd44d294852e0be500d9606d74186105514a1d18423f627baf17ba67f59f482730edcd9c9f64b89aedb9e4762c266615bd90e6fb0811258149';

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

        try {
            if ($code < 200 || $code > 204) {
                echo ($errors[$code] ?? 'Undefined error' . $code);
            }
        } catch (\Exception $e) {
            die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
        }

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