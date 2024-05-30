<?php
namespace services;

use PDO;
use PDOException;
use services\db\DBconnect;

class AmoCrmConnectAbstract
{
    private $clientId = 'edefe1b3-baf7-42af-899c-50621eb275b0';
    private $clientSecret = 'XY5vPveMNOdA6nurraTt8xbIFUoHLTRDUVkq1X9yenktOnPYgZlYMs9rFiXMY2QW';
    private $code = 'def5020083d13e73a2aff5d7a2a71c7564a8f0eb41db8ceab281943ffbb732319d9e377c0d207b3efae4632d17b9056daf3d611c2e97eb0ca8331c7501d69347b81d1a3003882e25444b02a439c3537cc581bea2c57e1c1cbd78b723de3afc173749caaaf0297b2d43f1f273160bb92a3dd2ae776e5e1008e14926874cf8012d34952e62fd2ac0bdc41fe59a87a4f44ccec660b66ddaf8f95be3ce35ef187570e466ee59eaa4832ed038ad660dbf7203dcb6c0accf353d1d5123c6c620d5cc54b445c2b8a2b55103511554274b84a2beee637939552d993f74be39224be5011f483f04a3352aa871c802121e693a5280e994724aa636b50ace8079faa71249bc02f6ddb257bac0d95c1a7df505cfa2ccc1262873eaaf82b6c2adc3ac75e8cc2e8cbd33a41c353829857de7a21c6f0391675ce96c615ff240f6c00083b46e69f6cf5534881d7ff7fef58f6c1e7e3d2a4b7e8889643d83f8220339a75916935a04841c6058dd2aa3507f0468e31de8b029f8a033b9932363112d2639630db7273d2fc473a48249f563d5d89257f79b6310226a854ce97c575c2559cd9acc5ac7a6344fe0e4b9ab69aa092f4eea44a08ed489b21a2d2c439ae7eff585fc1066e0c3b2ab8f722ba74c3f3e90c2e3ad1104d5e304c0f565eb8cc88dbb7b9bebc12a2335a23ac898c974855307b7';
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

            foreach ($tokenAssoc as $val){
                $sql = "DELETE FROM tokens WHERE id=?";
                $stmt= $conn->prepare($sql);
                $stmt->execute([$val['id']]);
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