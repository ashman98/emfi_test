<?php
namespace services;

class AmoCrmConnectAbstract
{
    private $clientId = 'edefe1b3-baf7-42af-899c-50621eb275b0';
    private $clientSecret = 'fCCIUC4ZgdOnyb8XP8Mtgr85dPMxjnrPpTVXuQegr6OmFrRbBr34BDwF5pXn0Hds';
    private $code = 'def50200b2e8e0e4913ddb21d7dd12f314a90e6451a7d9a4d08f20d6170a3e0c74acb167cfe3d43aac9d1e2d06e4c6de152b898de62d7ffd7f789c7d3e3a6867d65940c984de946fb726333b52e472d2fb99ff501d213c8231831f9bef392c1766e09f5597933521e84fa681adbdc45581a50a563c8d9dbe80cef31eabf7bfc301a409ee6878a722f0c15a48acc666ba9944cf7d47801cb5d070cf8ca18cf05dca9a27eaaa1b77a54c8603a05c6ff0151d8f5278dff22a0fd42b10875a33e9b383d02525ba455a243ee1ba765165c801be1d2ef1ad1afb995f1960ae1a3c9f396f484dc7ecc50a05765b947c525e25e223ed72145421ef1e20f8ca52036e0bb1702d9d97f4b37fae525276d981849998593713b1b48a41f560826dd58ee5b746db3aa401d776c73a92e17a4b67c5e160c0f91de6ce9ee45a86246bd03773773064fcd7a658fe830f92a5998272fa7e906df92f72868de294c7b751c8d3bd36da879047caf6cf9eef59391629b3048893f85c9a5c36ea491b02d3e24a75211e2550cf2ac80795f1f50c7fdb501b77058494254caf09e81f28b9d499618e664d14b6e46cf5116eb6ca318e9f10b736b78d5530e673cb7b48e9a89e3b6e5c1b1e493fff417041f80eb515da67e5a26b1a4a011616ee3dce3ede76006d13c2557e7f12f04742c87a94a502a0d7e523';

    private $redirectUri = 'https://pbl.up.railway.app/';
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