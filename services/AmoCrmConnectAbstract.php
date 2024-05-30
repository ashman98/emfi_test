<?php
namespace services;

class AmoCrmConnectAbstract
{
    private $clientId = 'edefe1b3-baf7-42af-899c-50621eb275b0';
    private $clientSecret = 'oLJTAmzRfoRUXbkRuMJTk8JomuPgPOTNypA1xdPkcIkN6yJydMKsF1CpPzNpgEbo';
    private $code = 'def50200a2411dbeb38121202ae31933ff9b4881fec6c34da0c1015f162f8963ef848fdbfd28450eaf1bb4daeeeabe69b76c36ee02c946523c6974aad66a72fd6b95ff0bfefb31fb45d3c4b1ea6007a98ed86a44d8ce0421188ca5f633629677cab3df4abe9a4d2940a56b97f1b66f75b56f675cfd3cac8b530b5968181f095fb66cd9276972691aa5c872dedc77ffed79d6686754273694a2e6a595a7ba105cf3234f683a6423a24363ff7d323455a1e32ba288355e73b558b29325749711fd6962fc6b8dc0f5a0ad097135d120d68b7c95b7dd699c2e7016219ea5c86321bf3ad070d8d9d2552eb8f6fa907a620e3b7000122adf78130e9900ac91146db4a7b29442ccafd5a707e3e2267b2fe29657b23243d7e66e236658e327820a76e0d9a63f1b92db37d6fc3aec4f0bfd04ab829edd58dfed5457168a82bbd9f5b18941a0341fd73ec58057597d59fae195bb8e62ae24e80c32c3a7326962e53ee90cb7f734e58335b0de057cc1ec6310cb92019ef3a1c13d5b2d9cb5dbfe94591910d6b0456eb2aab9c3f8108c53da11fafaa0e5cdf4fd16b5f314a3f3e4da67372a71c39cb9e4765a178f2b7f11951a5ba4995dc3dc0c385c14297cd08c6095f508fd319da6585c94d988219cebb8762c79f7a9ac421abee73e021452fba8c71e6ce19ea0903f8c25ec0e9447750396';
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