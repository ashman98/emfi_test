<?php
namespace services;

class AmoCrmConnectAbstract
{
    private $clientId = 'edefe1b3-baf7-42af-899c-50621eb275b0';
    private $clientSecret = 'RdciNNMN6RMV7LvnhJBtHwQVJu4LnW12IsfVdo3X2CgU4oTDvmUdPrh6rLjg7P8E';
    private $code = 'def502000f217cebaaa53d46a976ec659151f8f9f8819c4282a509694c5e2b689b9004bf94ff8c087e0a108f14f06c4d4c3657ad3d01581551b59b4e598885427ec0d80a5c07ba4de16c456c01b29f8fa288427fa2c940c3e193b0585cd81626f9db659312f482cea3a7ed11cde0e5e2af453c9244e295eebdd774f9821bb6a9c4ba75b40761d2009f442630b631cc5cba660cfe9a753187d41175d62f95f7ae54343510e37896224ae40fbfacb839717e7f99f0fa19e37bc1c9ddb924d51e3dc32e07a047e428c2418c646bae690bec864d8d0c00c501abda28ab1e2f42115dcac2eda6434ecca62da11cf91707c8e2a0295d256b52b3bdfa7795c0377b81e54d94aafa79d704b2598df18a500a69e30ab5cc0d4b2a1aa499920df419cdc290fb67afccf04c496468a05a2e801024b9062a88d4256983465b34a2215c1bffcb2798fed57457c7ce8f73a22dbe86a863920153a7c20c74e70ee072783a2eec701f9a5a87f3ae680afa5140cb1aabc51f4e2be79aafcaac15e92477d6d868707a2480006363821168115a22b27051079eb22bcb1a84b75e91044cb164a9e5a84c34e69fb822e233ded95f3b704d3c4632c659014cb67f89455c1c526471343648ef493bff5671519761541e381a0a60933219369d56b680b19d311be9a5db2c58ee24332b215848f70525e4e243c4610ccc54bf87579d386551dcf4a210555a32';
    private $redirectUri = 'https://see-through-weights.000webhostapp.com/';
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