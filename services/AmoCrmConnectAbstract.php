<?php
namespace services;

class AmoCrmConnectAbstract
{
    private $clientId = 'edefe1b3-baf7-42af-899c-50621eb275b0';
    private $clientSecret = 'SH3LV3iA5CGI6hjmHH0ySW5SWQyH0GrwzqJPPofIxclAse61ljzd9oIbxq1h8EPU';
    private $code = 'def502001ce58801443d7932519299d9d32e776403019da79864e2f23f970807faee71444cc623711eda92073755eba4483e549b9d4a9d565ab86458f456fb45ce99a971b4eb90b32ee63c5033ef29b4c571fdcba7cd148ef5a6dfd9b2a0e0a28c0206b6c9ef10142721fde35e418e59d769ccaf222767869c63bb023f22681074b8114027729a3d8b2be6b6007df254007560dd4934b2f6f8f97cd0bfb9992427e4e749842efb281b7f3fc571ca81d5e19a1d5da0db5939b223c1dff991e422d57cd18f9c8275f42240f9a39eb097004542c794c174556ba8edc4d878149ad9657aeabb9460a0e708a9b2861567b3c97bbcd5aa25850fdfd96ca0ca522b241092f4667e4b231d3e7a60e11115db348898e5f0a74e8e8e986b9fb1d1b2a1faba709896ba6ee0107d985cdb63dd63dac9d8e511c457c7527877149803e0fb21ed7cda06d9cf9ed7ed08ed2edd582fb255770b0ed7b9f398054cc018ca09c7b438b51722fc4582bb2a5becd41b667c1d8d2c2580d55031d15f8455d96a5abef6ba1439654197504f9f1cb8cdafcf54e6a20040ae4fb71d2f9c25fd062cc0e8193c938dc63cccce3b8027015fb00e234bfa01c6474b6e5a4fb819e7c4af5269ee2ddcb72da9e8dd21d6f1b99d6cb0761d2bf4ab5a87bbe9b8215f186c73246c507b60eb03cd84eda6f017660f';
    
    private $redirectUri = 'https://pbl.up.railway.app';
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
        print_r(json_encode($response));

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
        } else {
            die('Ошибка: Invalid response from authorization server');
        }
    }
}