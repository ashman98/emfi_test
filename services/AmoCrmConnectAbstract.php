<?php
namespace services;

class AmoCrmConnectAbstract
{
    private $clientId = 'edefe1b3-baf7-42af-899c-50621eb275b0';
    private $clientSecret = 't32djzEe2yvpYbR4I9j4iMkM2aJri5KVKWS5cD40GnKgiB5Rj7TEcDj5wgRIdHc5';
    private $code = 'def5020072cccd9868fe33869a9630b7067fc1fddc770ac5f8ce0b57de0f5cf941fa7d25ed135289871e0c36c2cc42b537a5ffa6f8e079a9c82e0b04dbb06e3cabda0c5816c246a04515753bc2e9812c9b6740ca146531cbb173fe22a8d012bbd263dbecd1f808e52cd78f074f75808baf847f54964c13e7692c6b15c172db4091866dda6d6549f3de871a6e0bbe88a89daff5a83ea7d836c03fdecc1ec802c8d3c0c57e7c58074fc680717d0e344f0d39b4cbefafec556b54a91f05e1f3b0808194353a8755bf181df7c1c0abe435853bb0c00960f56fefc772d77fbe5e2211932c1fe0a21257492f6e235b539ee0483c9a599bc6483ecf2c4d2d3363ab8d4132518e089ef8ce9fb3aa078e7dcdfa594c94f58d20428d05f11c0a0ad8de2740d3b4297c4be78d3584b8ea580edfe14c5e570fd356068790964f1bc572bf5a416822d5eac67c5b8b4ac997c6f7a99942e3e2cd6ee805efec1a5d5648909eae34b27aad31836c6a6977ad6d291c1d3098c85b50c55f4d0d0821ca2299ad3e9f372ef11f1a29251987707d53b7227ff5b1ee354039544f66c9b194dbdf7357841e6f838208604c2b69c9ffb8b4616ed9787e64ad8fe1d6f0278afc8015cee4d3d8276642a70236d8508a5ab6c7a4385e884b0f68d142c84630fe42de8348e210fbbc1a82eef332adb5d2334a';

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