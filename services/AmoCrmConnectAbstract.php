<?php
namespace services;

class AmoCrmConnectAbstract
{
    private $clientId = 'edefe1b3-baf7-42af-899c-50621eb275b0';
    private $clientSecret = 'oLJTAmzRfoRUXbkRuMJTk8JomuPgPOTNypA1xdPkcIkN6yJydMKsF1CpPzNpgEbo';
    private $code = 'def502001d463593d67b8277c4cc6ea41e18ebfb3b2544f1964fcfcc1f382a0d9ed188b083b80f5cdc23dde9aa6dfcdea5621e2deae6e4bcee79b04356e9ea0348f51697ac25fc48a856da97e3e8b880b7b61b443c6040d2895388d9f92627595a3534f96799455777cf3fd7b338f082ef2ced4ba7d025dee68d8bac0e46576a230e62eb54cea3e4a6bd02065732afcb24deec1dbeed8705364652e4c2d9fb5e29cec9e6160ab211d8646ec0666622c22d47bc2cc0f04ada6d5c429e5916e7538d46ac8cbc7887245976f3250c4c021317206310681a2bcf486b3e89efcfcf680d69f5ce2dbb8417be0a3d7d2a46f66a34eab705227d3605014e9038e6b3c16f251f7e6ae2ed1849a3d0f0f3f6850ce8bc92cba7e36f69d72ae4b8803946f3950daf53c7052f01de0af1b48ee4aedf4fa7576e9b4d95f5e56c9edc4099345f36ff0ad93c62aff94efec8bc3faeb8572f14d5295bf5b62fc157e288f661c57894b43fe9b058807bbd1988d2c029450aa33302564833bcf789d4a9d2d5ac7778fdb617cccfe3f47419a2a11cbe9b395ecf2e5c4ceb7af50c38a4dc4979a4dc4b2a1deb2677d1227565d57bc3db9df626e1e295a079aa46b6481c4855854d3b044b5121bb39af90c7b4ee06c93ec0ff21fb8991fb68ed73b9582e3e56935f617f411ac72ad99430c6da8cc993ab2a';

    private $redirectUri = 'https://pbl.up.railway.app/';
    private $refreshToken = '';

    protected $subdomain = 'emfitestmailru';
    protected $accessToken = '';

    public function __construct()
    {
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
                throw new \Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
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
        } else {
            die('Ошибка: Invalid response from authorization server');
        }
    }
}