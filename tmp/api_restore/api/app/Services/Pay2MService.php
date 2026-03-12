<?php

namespace App\Services;

use App\Models\PaymentInfo;
use App\Models\V1\RifaPay;
use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Schema;

class Pay2MService
{
    protected $client;
    protected $baseUri;
    protected $clientId;
    protected $clientSecret;
    private int $qrCodeExpirationTime = 1800;
    public function __construct() {
        $this->clientId = '38cd89b9-ef9f-4a09-ad2f-b6ca82048a51';
        $this->clientSecret = '74d5f557-128a-4dc4-8db8-6484f53842d7';

        $this->baseUri = 'https://portal.pay2m.com.br/api';

    }

    public function getToken() {
        try {
            $client = new Client();

            $authHeader = 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret);

            $response = $client->post($this->baseUri . '/auth/generate_token', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => $authHeader,
                ],
                'json' => [
                    'grant_type' => 'client_credentials',
                ],
                'verify' => false,
            ]);

            $authResponse = json_decode($response->getBody(), true);
            return $authResponse['access_token'] ?? null;
        } catch (RequestException $e) {
            return [
                'status' => false,
                'message' => 'Erro ao obter token: ' . $e->getMessage(),
            ];
        }
    }



    public function createPayment($rifa, $description) {
        // Obtendo o token
        $token = $this->getToken();

        if (!$token) {
            return [
                'status' => false,
                'message' => 'Falha ao obter token',
            ];
        }


        $value = floatval($rifa->value);


        $payload = [
            'value' => $value,
            'generator_name' => $rifa->client->name,
            'generator_document' => $rifa->client->document,
            'expiration_time' => $this->qrCodeExpirationTime,
            'payer_message' => $description,
        ];

        try {
            $client = new Client();
            $response = $client->post($this->baseUri . '/v1/pix/qrcode', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
                'verify' => false,
            ]);


            return json_decode($response->getBody(), true);

        } catch (RequestException $e) {
            return [
                'status' => false,
                'message' => 'Erro ao criar pagamento: ' . $e->getMessage(),
            ];
        }
    }

    public function checkPaymentStatus($paymentId) {
        $token = $this->getToken();

        if (!$token) {
            return [
                'status' => false,
                'message' => 'Falha ao obter token',
            ];
        }

        try {
            $client = new Client();
            $response = $client->get($this->baseUri . "/v1/pix/qrcode/{$paymentId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ],
                'verify' => false,
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return [
                'status' => false,
                'message' => 'Erro ao verificar pagamento: ' . $e->getMessage(),
            ];
        }
    }
}
