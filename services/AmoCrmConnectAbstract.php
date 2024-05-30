<?php
namespace services;

use PDO;
use PDOException;
use services\db\DBconnect;

class AmoCrmConnectAbstract
{
    private $clientId = 'edefe1b3-baf7-42af-899c-50621eb275b0';
    private $clientSecret = 'p1doK1KNLHx8cHLyKtph6Guy0nvPb1Bzf4blEqOfdhweLhLjCXv5HUipZX4bXDhh';
    private $code = 'def50200161292ebc286ec8a8cab950f0fb04a61418dd046f7bde458d80ab15881e1b735c388fa52f22a42ef462d41e5e827275948833b2f7f5e063f36c036d814f23482b37beb87f856ddcf0fe33d50fd0a4ab85420c55440012ea0b3798aaeb0fa23070dd44e1988f866c63cd2dbda259f7b7f551f1cb9721b4e85d93082d77f937a6b9bfd67ac5374247e028a86c8f097103d393f40b9dad81828d5aaf1e7cfaaddeb05d86fec0a900c31af55f76a24c10882341f1e14b763134271f2d9111d374ab318e8e2f93e7231eee1acd8c19229f867a4234da6588e3a6cbb48f9a4acd2b73d4f3b7e6af106acf3efdd0d66e118778db7ce6dc409c8a080fec380640d6af53edf0cc6c33147c8877120293f76487cbfdaf4dc2f1b55375ced5e09e3954bcc6543c5021a8a2d0e0058c20f1363f05ac1fa8e61dc6aface33c71624ccab45cdb8ff79101e792f52341c9b39b9c2a1c6918e6409d2aac0bb3b242794a405cbef2c5e0acdcf6b7ec77589b1a8842be7035fd488373a2f462501296165846c727632cec01737b97a8cd441742d1e6011b7b617a452dee66689ac0ec8bfbb06ef59e5f255dd790a161a1db71577bbaecdc260e28e16483d2dad456970b2d82d1b962f42e0b00f38bdb70e20d9ef4410835c823610c90eb2616adcb47bc56be127aa9630aed7102e8de0';

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
            $selectToken = $conn->prepare("SELECT * FROM tokens ORDER BY id DESC" );
            $selectToken->execute();
            $tokenAssoc = $selectToken->fetchAll(PDO::FETCH_ASSOC);

            if(!empty($tokenAssoc)){
                foreach ($tokenAssoc as $val){
                    $sql = "DELETE FROM tokens WHERE id=?";
                    $stmt= $conn->prepare($sql);
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