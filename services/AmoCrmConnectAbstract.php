<?php
namespace services;

class AmoCrmConnectAbstract
{
    private $clientId = 'edefe1b3-baf7-42af-899c-50621eb275b0';
    private $clientSecret = 'AEpkwrigQBw84OMe6BYmbQFKl5mcQd4LyL5f5EjW0Fsyd2ClOe5tqcL16CBJrw0U';
    private $code = 'def5020068ddea83f07c3ba9bf643152443f923a03d5f8a58a2edebceed5600b94d7324fbb12475ad7ba1b80e6780bac41df42eecde735c92a4e60957b9580a7f86c7e35f997c05c79bdfe398b167a72775d9a2b8e858bd85181d47378e2ce7e09fa430b4eb6da549bac493f528838a3eaaae0ccc9b4745edbb2c3048fce1adba053dc3666d9ae24529a2f61429b50841ce3bbea9f71b7d48afc68a9d75000d018636f7d5a03c7a308d596c0895571a30c0c70e6ee89f7af68898bdbf29093d0823c72ad3ba068d5a7c431f596afa83bf3609ad5c544a52fc18a75a8bbff4e28aa77a63999c8189c88302466e9c1a759cbbeb2cedabfd3950b9939e0db90b95b93952ef98aab9b997ac1856daee64012c0a394c43ed4b1f0e3647770d9b2668ee8da524639d751d53bfeafdb86006fb6fcc65376c6d3e7bd3c1f6d9e4b9a9f710c6a7682f73a088e87b518b9ab98c53f794ed322e97cf06e6dc5591f00f0ab8b632327d8ccb5ef5d1e603eee4ad98a27ac40a16bf709ebedf9d692da5d1d11244b60cb804e968138ab422369d837fd07591626b91b2ad1c3a553265a6b7233487ee0c2dd12b58c7db612601aa722067843b6e1715d69e658d5052a29a41730542b220cea58536bd6eb4a273dbf17b7347daee7710a710070655b8f62bb68ac7586bc586d3c8e47cf39b1d1';

    
    private $redirectUri = 'https://pbl.up.railway.app';
    private $refreshToken = '';

    protected $subdomain = 'emfitestmailru';
    private $refreshTokenFile = 'refresh_token.txt';
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
        file_put_contents($this->refreshTokenFile, $token);
    }

    private function loadRefreshToken()
    {
        if (file_exists($this->refreshTokenFile)) {
            return file_get_contents($this->refreshTokenFile);
        }
        return '';
    }
}