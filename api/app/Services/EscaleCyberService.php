<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class EscaleCyberService
{
    private string $apiKey;
    private string $baseUrl;
    private Client $client;

    public function __construct()
    {
        $this->apiKey = env('ESCALE_CYBER_PUBLIC_KEY') ?? env('ESCALE_CYBER_SECRET_TOKEN', '');
        $this->baseUrl = 'https://api.escalecyber.com/v1';
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'X-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'verify' => false, // Desabilitar SSL se necessário para testes
        ]);
    }

    public function createPixTransaction(array $data)
    {
        try {
            $response = $this->client->post('/payments/transactions', [
                'json' => [
                    'amount' => (float) $data['amount'],
                    'customerName' => $data['customerName'],
                    'customerEmail' => $data['customerEmail'],
                    'customerPhone' => $data['customerPhone'],
                    'customerDocument' => $data['customerDocument'],
                    'customerDocumentType' => $data['customerDocumentType'] ?? 'cpf',
                    'description' => $data['description'] ?? 'Compra de Rifas',
                    'metadata' => $data['metadata'] ?? [],
                ]
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() === 201 && isset($body['success']) && $body['success']) {
                return [
                    'success' => true,
                    'transaction_id' => $body['data']['id'],
                    'external_id' => $body['data']['external_id'] ?? null,
                    'status' => $body['data']['status'],
                    'qr_code' => $body['data']['pix']['qrCode']['emv'] ?? null,
                    'qr_code_base64' => $body['data']['pix']['qrCode']['image'] ?? null,
                    'amount' => $body['data']['amount'],
                ];
            }

            Log::error('EscaleCyber Error: Invalid Response', ['body' => $body]);
            return ['success' => false, 'message' => $body['message'] ?? 'Erro desconhecido ao criar PIX'];
        } catch (\Exception $e) {
            Log::error('EscaleCyber Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getTransactionStatus(string $transactionId)
    {
        try {
            $response = $this->client->get("/payments/transactions/{$transactionId}");
            $body = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() === 200 && isset($body['success']) && $body['success']) {
                return [
                    'success' => true,
                    'status' => $body['data']['status'], // PENDING, APPROVED, EXPIRED, FAILED, REFUNDED
                    'paid_at' => $body['data']['paidAt'] ?? null,
                ];
            }

            Log::error('EscaleCyber Status Error: Invalid Response', ['body' => $body]);
            return ['success' => false, 'message' => $body['message'] ?? 'Erro ao buscar status'];
        } catch (\Exception $e) {
            Log::error('EscaleCyber Status Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
