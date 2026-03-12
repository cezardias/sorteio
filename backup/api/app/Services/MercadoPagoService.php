<?php

namespace App\Services;

use App\Models\PaymentInfo;
use App\Models\V1\RifaPay;
use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Schema;

class MercadoPagoService
{
    protected $client;
    protected $baseUri;
    protected $clientKey;
    protected $clientSecret;

    public function __construct() {
        // Verifica se a tabela 'payment_info' existe antes de fazer a consulta
        if (Schema::hasTable('payment_info')) {
            $gat = PaymentInfo::where('name', 'paggue')->first();

            if ($gat) { // Verifique se a consulta retorna um resultado
                $this->clientKey = $gat->public_key;
                $this->clientSecret = $gat->api_client_id;
                $this->nomeFatura = $gat->billing_name;
            } else {
                // Defina valores padrão caso a consulta não retorne nada
                $this->clientKey = '';
                $this->clientSecret = '';
                $this->nomeFatura = '';
            }
        } else {
            // Defina valores padrão caso a tabela não exista
            $this->clientKey = '';
            $this->clientSecret = '';
            $this->nomeFatura = '';
        }

        $this->baseUri = 'https://ms.paggue.io';
    }

    public function getToken() {
        $client = new Client();
        $url = "https://ms.paggue.io/auth/v1/token";
        $headers = [
            'Content-Type' => 'application/json'
        ];

        $payload = array(
            'client_key' => $this->clientKey,
            'client_secret' => $this->clientSecret ,
        );
        try {
            $paggue_curl = curl_init();

            curl_setopt_array($paggue_curl, array(
                CURLOPT_URL => 'https://ms.paggue.io/auth/v1/token',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode([
                    'client_key' => $this->clientKey,
                    'client_secret' => $this->clientSecret,
                ]),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                  ),
            ));
            $auth_response = json_decode(curl_exec($paggue_curl));

            curl_close($paggue_curl);
            return $auth_response;
        } catch (RequestException $e) {

            return $e->getMessage();
        }
    }

    public function createPayment($rifa, $description) {
        $tokenResponse = $this->getToken();

        if (!isset($tokenResponse->access_token)) {
            return [
                'status' => false,
                'message' => 'Unable to obtain access token',
            ];
        }

        $paggue_company_id = $tokenResponse->user->companies[0]->id;
        $token = $tokenResponse->access_token;
        $value = intval(round(floatval($rifa->value) * 100));



        $timeLimit = $rifa->rifa->rifaPayment->time_pay ?? 30;
        $expirationDate = now()->addMinutes($timeLimit)->setTimezone('UTC')->format('Y-m-d\TH:i:sP');

        $currentDateTime = new DateTime();
        $formattedDateTime = $currentDateTime->format('YmdHis');


        $uniqueExternalId = $rifa->client->id . '_' . uniqid() . '_' . $formattedDateTime;
        $desc = !empty($this->nomeFatura) ? $this->nomeFatura : $description;

        $payload = [
            "payer_name" => $rifa->client->name,
            "amount" => $value,
            "external_id" => $uniqueExternalId,
            "description" =>  $desc,
            "expiration_at" => $expirationDate
        ];

        // Calcular a assinatura com a hora atual e o ID do cliente
        $signature = hash('sha256', json_encode($payload) . $token . $formattedDateTime . $rifa->client->id);

        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
            'X-Company-ID' => $paggue_company_id,
            'Signature' => $signature,
        ];
        try {
            $client = new Client();
            $response = $client->post('https://ms.paggue.io/cashin/api/billing_order', [
                'headers' => $headers,
                'json' => $payload,
                'verify' => false // Desativa a verificação SSL temporariamente
            ]);
            // Verifique a resposta antes de retornar
            $responseBody = json_decode($response->getBody(), true);

            if (isset($responseBody['error']) && $responseBody['error']) {
                // Log ou debug da resposta completa
                return $responseBody;
            }
            return $responseBody;
        } catch (RequestException $e) {
            // Log ou debug do erro completo
            return $e->getMessage();
        }
    }





    public function checkPaymentStatus($paymentId) {
        $tokenResponse = $this->getToken();

        if (!isset($tokenResponse->access_token)) {
            return [
                'status' => false,
                'message' => 'Unable to obtain access token',
            ];
        }

        $paggue_company_id = $tokenResponse->user->companies[0]->id;
        $token = $tokenResponse->access_token;
        $url = 'https://ms.paggue.io/cashin/api/billing_order/'.$paymentId;
        $headers = [
            'X-Company-ID' => $paggue_company_id,
            'Authorization' => 'Bearer ' . $token,
        ];

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get($url, [
                'headers' => $headers,
                'verify' => false
            ]);

            $responseBody = json_decode($response->getBody(), true);

            return [
                'status' => true,
                'data' => $responseBody,
            ];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $response = $e->getResponse();

            if ($response) {
                $errorBody = json_decode($response->getBody()->getContents(), true);
                return [
                    'status' => false,
                    'message' => $errorBody['message'] ?? 'Erro desconhecido',
                ];
            }

            return [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }




}
