<?php

declare(strict_types=1);

namespace AstrotechLabs\Pay2MSdk\CreatePixCharge;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use AstrotechLabs\Pay2MSdk\CreatePixCharge\Dto\PixData;
use AstrotechLabs\Pay2MSdk\CreatePixCharge\Dto\QrCodeOutput;
use AstrotechLabs\Pay2MSdk\CreatePixCharge\Dto\CreatePixChargeOutput;
use AstrotechLabs\Pay2MSdk\CreatePixCharge\Exceptions\CreatePixChargeException;

final class CreatePixChargeGateway
{
    private GuzzleClient $httpClient;
    private string $baseUrl = 'https://portal.pay2m.com.br/api/';
    private int $qrCodeExpirationTime = 1800;

    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret
    ) {
        $this->httpClient = new GuzzleClient([
            'base_uri' => $this->baseUrl,
            'timeout' => 10
        ]);
    }

    public function createCharge(
        PixData $pixData,
        bool $manualToken = false,
        string $token = null
    ): CreatePixChargeOutput {
        $token = $manualToken ? $token : $this->authenticate();
        $body = [
            'value' => $pixData->value,
            'generator_name' => $pixData->generator->name,
            'generator_document' => $pixData->generator->document,
            'expiration_time' => $this->qrCodeExpirationTime,
            'payer_message' => $pixData->message
        ];

        try {
            $response = $this->httpClient->post("v1/pix/qrcode", [
                'headers' => [
                    "Content-Type" => "application/json",
                    'Authorization' => "Bearer {$token}"
                ],
                'json' => $body
            ]);
        } catch (ClientException $e) {
            $responsePayload = json_decode($e->getResponse()->getBody()->getContents(), true);
            throw new CreatePixChargeException(
                1001,
                $responsePayload['error'],
                $pixData->values(),
                $responsePayload
            );
        }

        $responsePayload = json_decode($response->getBody()->getContents(), true);

        $options = new QROptions([
            'version' => QRCode::VERSION_AUTO,
            'imageTransparent' => false,
            'outputType' => QRCode::OUTPUT_IMAGE_PNG
        ]);
        $qrCode = new QRCode($options);

        return new CreatePixChargeOutput(
            gatewayId: $responsePayload['reference_code'],
            copyPasteUrl: $responsePayload['content'],
            details: $responsePayload,
            qrCode: $qrCode->render($responsePayload['content'])
        );
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function authenticate(): string
    {
        try {
            $response = $this->httpClient->post("auth/generate_token", [
                'auth' => [$this->clientId, $this->clientSecret],
                'json' => ['grant_type' => 'client_credentials']
            ]);

            $responsePayload = json_decode($response->getBody()->getContents(), true);
            return $responsePayload['access_token'];
        } catch (ClientException $e) {
            $responsePayload = json_decode($e->getResponse()->getBody()->getContents(), true);
            throw new CreatePixChargeException(
                1001,
                $responsePayload['error'],
                [],
                $responsePayload
            );
        }
    }
}
