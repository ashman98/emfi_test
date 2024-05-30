<?php
namespace services;

class AmoCrmConnectAbstract
{
    private $clientId = 'edefe1b3-baf7-42af-899c-50621eb275b0';
    private $clientSecret = 'AEpkwrigQBw84OMe6BYmbQFKl5mcQd4LyL5f5EjW0Fsyd2ClOe5tqcL16CBJrw0U';
    private $code = 'def50200531a4b5d30cab83848e17dcc612f261163e36d5b4063d463c7f00886b075dec9e378152455f9eb114baa11a01e406f162dba867efa2e27d2d2461450c326ce4e63d70df5a29ef848a64860bed077b416a4ecbafc911bc6c80047438c9943647a63093938554a7c099c56be6491ba9333cf15b88bf8e6b24fc4aceeebfb674f6076e83fd27ad9766a2e9342b225a97481ae4aef10d3b5d8ffd33d5564caa5c078c2766b8356b7379c18bf21efedec83e25a14870f4c33825d2517570566ffb24cca8cc4db0a281ada2dcbc7ea92324689efac032bfe73712ccd682d89cfcdc8f356577434a042dd5e8d5ab42805596b1b1b46b05d7fbe35116dc2410cc5a86a0f39b0bf229fc939488364c5d3b3e3194750ecd7d6d15991086e818ce8bc301ed4c7efcfabc5c00936289b4f3608835e99741c5719cbcdad2626683627d74fc277eb2f14c50e3d396e58c212392983329e3cd5583ec1ebf00405e1072ad0fc90097f78880a1d014d350d2780ac97b9bdc981edcaac7172be47d286b60c4db15d470848dfef6124692de00b21de475d26cda5400b98481dc4b7f32bbeeaf632d9bad30551801c519185a31bddd7c0dba875a613625433f452ec920c3d5eeaccf99b32fcfcebaade08e1b3660e6f200e0dc771aff084187b5350cab784a8763abc7ef61f6b0beac03d';

    private $redirectUri = 'https://pbl.up.railway.app';
    private $refreshToken = '';

    protected $subdomain = 'emfitestmailru';
    protected $accessToken = '';

    public function __construct()
    {
        session_start();
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
        $_SESSION['refresh_token'] = $token;
    }

    private function loadRefreshToken()
    {
        return $_SESSION['refresh_token'] ?? '';
    }
}