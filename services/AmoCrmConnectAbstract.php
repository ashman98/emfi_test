<?php
namespace services;

class AmoCrmConnectAbstract
{
    private $clientId = 'edefe1b3-baf7-42af-899c-50621eb275b0';
    private $clientSecret = 'x5bALOGtncV0JrBqDhFd8QUrKLYMkawhXsfN13eYcWpMWmrySCk5VbnZbEfXm8lM';
    private $code = 'def5020047fd6dd2a55aa5452636ac605013044b97a64620fb0e0f0cfd9a4eb174892ea24cd2958a77899b215bb0dbb247f706ae48e136b326f5972054211a28c082a2f69b1c37eb5c2a3bc07239b2fbebe1313ad0043531bc7b9f9dad849935cc73bd6b02b7c6ff2587330744e3ba952bdf3dd57568900b05f52c1ebcb885fc664171a9cc8f0a78dc4fd4b57274b841f4ba331cfb4b872c17c7042919c8d4b3c0c362e21485df422cae7484a0e87dab740c24dcd0d3e054138f53697ffeb55891bc2f12affc59f5d216e5334627957bf811e65cbbd34e91de392c8254a7115fa3ee19ec1d14de8893ae0f0ca02a2ec9d1792b40bf8f5b8a2e586c908768388f0b3c2f9552d296ab7c338bead9cd09e99597128336288e7723e32f41a6c219782cfee8421b495948d366afa52e6ed73ac0c35d621c186a997b587d5ecefbe46d775e6e3d65bc224bd15483c050e30b92927def365e375589c6c9ff681a4412b331df6b86ba13353b6a83980058b44367842908d7a1f74f66a23557535b38c4ea51da2d5baadcec37782d7ba5177044a895569275bb0faf729ae7d2b71f4686976561b9cd0a7b10d626778bc27922685a92eb1f14fd87584af5c699331ba9bcc8ddb20f0be1e3ac1fc7bf77e06da6e72b95246d796ab3032ea5990cdde6bbfd10910e09231f2fce0fa22c20';

    private $redirectUri = 'https://pbl.up.railway.app';
    private $refreshToken = '';

    protected $subdomain = 'emfitestmailru';
    protected $accessToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6Ijc5OTBkMjM0ZGY0MWNlNDU2ZjQzODkyZWIxNTA2MzI3NTVhYTZlODgzMTdmOGE4NzRlZDJhYjZiYzA1ZGRlM2M1MDUzYjBlZmFkODU3OWNhIn0.eyJhdWQiOiJlZGVmZTFiMy1iYWY3LTQyYWYtODk5Yy01MDYyMWViMjc1YjAiLCJqdGkiOiI3OTkwZDIzNGRmNDFjZTQ1NmY0Mzg5MmViMTUwNjMyNzU1YWE2ZTg4MzE3ZjhhODc0ZWQyYWI2YmMwNWRkZTNjNTA1M2IwZWZhZDg1NzljYSIsImlhdCI6MTcxNzA4NDIzMCwibmJmIjoxNzE3MDg0MjMwLCJleHAiOjE3MTkwMTQ0MDAsInN1YiI6IjExMDg3MzUwIiwiZ3JhbnRfdHlwZSI6IiIsImFjY291bnRfaWQiOjMxNzY4NDI2LCJiYXNlX2RvbWFpbiI6ImFtb2NybS5ydSIsInZlcnNpb24iOjIsInNjb3BlcyI6WyJjcm0iLCJmaWxlcyIsImZpbGVzX2RlbGV0ZSIsIm5vdGlmaWNhdGlvbnMiLCJwdXNoX25vdGlmaWNhdGlvbnMiXSwiaGFzaF91dWlkIjoiYjU2ZDE1Y2QtMTliOC00NmU5LTllM2ItMzk2NTliNmMyOTBlIn0.OOhQIA-RybxKz5mhZkzhGiFqxNAohCNMcGXyKHz8YPu17-jLmuputh2EolZXFwp0Jf0-czNexzD2Vw8TMKLKxO_d6bnPXTN5MtO3qa5HMQM39_tt4J2LzqDMH_7wZ5_JXxUDO644cCtggxGqz17n1CVHTvLI3_yOQarKKkHkXzMLV1a82YiDA8xw-zyDco1_Z667RfzYmk9YZfAkS-hqILz_oCMaApVByjRUgY7050HKDbg9eYfV6Rd7WyHhT5dG-2ESCQ_KISzkFPfXndMJFvePlWzjJ4i3yXSeGQL3OPk-7-hwm_S1KNZdeZ3eCaA0W21lkgXXYuZlqfwknPFkKw';

    public function __construct()
    {
//        if (empty($this->refreshToken)) {
//            $this->authorize();
//        } else {
//            $this->refreshToken();
//        }
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

//        $this->handleResponse($response);
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
            echo 'Authorization success';
        } else {
            echo('Ошибка: Invalid response from authorization server');
            die();
        }
    }
}