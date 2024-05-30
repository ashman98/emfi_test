<?php
namespace services;

class AmoCrmConnectAbstract
{
    private $clientId = 'edefe1b3-baf7-42af-899c-50621eb275b0';
    private $clientSecret = 'J4K5n6gkyoED4VTWuMIyaUnwOIoDSzjv5sqGsakSzoP9Xm3jwKmHpnWSODDYNA46';
    private $code = 'def50200c60f1f55019ace7a849ed6027e1ed5f7c50898b18083f62f2e23c131d5f8f1f1899b1ded4834f356a0bf8598527eee67411d0f24b190d55e2159306fd12cb5b0c09de27dc6b735b0f3c20552f54ee4999e0fd00bab18752f5b1c91cea4df6e6a0d33f832b4f6a642f033b1892a6566b0de4fb8ccf02e3908cef18ea6a5c726340c9b2c91c1bce97d47fbb06fc14d1e9463a7611a002fe09e9c3760f6cab8a0996714b1e19e7430e5341638453eb4dd0f76311ad7a690faf330ad7f999197185bad3c7d131c9a28a38db2bfd509b339d5a6612d0c0cdcd4615f4081bfc280fbb44637cd63e49380bd7a747d20684be481cde132e8f441f3d80d8dba95f5a14d7373687daccd68534c34daa173bff39a3c14698956754d9f64df829ca86475fe981b4cd8424f94485278d902a3ede9daeea9b61c68d38c9a83d364ca47869eeb186f75a7449cf47e48e6c31b12d84af73a241a1aa22a422489d40042ecdc52b1bd2317d3fa90b247e98db22ca77b00f7bc9707105d7269e1f5c9f78067428c6594c218354420cb8e8045191f4121c6c13c2e90048fbe107f0d8de86341529f9aeb0eb1fb577a48e86fd680cc5204f16404b044681c7579ad052fcb06a3259bb724c968f47f07013cd04f2398d5004655b4f97470bc6f2c9bc0558002427cf976b87081452402e724e076';
    
    private $redirectUri = 'https://pbl.up.railway.app/';
    private $refreshToken = '';

    protected $subdomain = 'emfitestmailru';
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
        setcookie('refresh_token', $token, time() + (86400 * 30), "/");
    }

    private function loadRefreshToken()
    {
        if(!isset($_COOKIE['refresh_token'])) {
            return ($_COOKIE['refresh_token']);
        }
        return  '';
    }
}