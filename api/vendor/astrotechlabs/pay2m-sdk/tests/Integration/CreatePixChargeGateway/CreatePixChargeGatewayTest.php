<?php

declare(strict_types=1);

namespace Tests\Integration\CreatePixChargeGateway;

use AstrotechLabs\Pay2MSdk\CreatePixCharge\Dto\GeneratorData;
use AstrotechLabs\Pay2MSdk\Pay2MGateway;
use AstrotechLabs\Pay2MSdk\Pay2MGatewayParams;
use Tests\TestCase;
use AstrotechLabs\Pay2MSdk\Enum\BillingTypes;
use AstrotechLabs\Pay2MSdk\CreatePixCharge\Dto\PixData;
use AstrotechLabs\Pay2MSdk\CreatePixCharge\Dto\QrCodeOutput;
use AstrotechLabs\Pay2MSdk\CreatePixCharge\CreatePixChargeGateway;
use AstrotechLabs\Pay2MSdk\CreatePixCharge\Exceptions\CreatePixChargeException;

final class CreatePixChargeGatewayTest extends TestCase
{
    public function testItShouldDefineCorrectUrlWhenNotInSandbox()
    {
        $sut = new CreatePixChargeGateway($_ENV['CLIENT_ID'], $_ENV['CLIENT_ID']);

        $this->assertIsString($sut->getBaseUrl());
        $this->assertNotEmpty($sut->getBaseUrl());
        $this->assertSame('https://portal.pay2m.com.br/api/', $sut->getBaseUrl());
    }

    public function testItShouldAuthenticateCorrectly()
    {
        $sut = new CreatePixChargeGateway($_ENV['CLIENT_ID'], $_ENV['CLIENT_SECRET']);
        $token = $sut->authenticate();

        $this->assertIsString($token);
        $tokenParts = explode('.', $token);
        $this->assertCount(3, $tokenParts);
    }

    public function testItShouldCreatePaymentChargeWithoutToken()
    {
        $sut = new CreatePixChargeGateway($_ENV['CLIENT_ID'], $_ENV['CLIENT_SECRET']);
        $response = $sut->createCharge(new PixData(
            generator: new GeneratorData(name: 'John Doe', document: '02993405381'),
            value: 1,
        ));

        $this->assertIsObject($response);
        $this->assertNotEmpty($response->gatewayId);
        $this->assertNotEmpty($response->details);
        $this->assertNotEmpty($response->qrCode);
        $this->assertIsArray($response->details);
        $this->assertArrayHasKey('content', $response->details);
        $this->assertArrayHasKey('reference_code', $response->details);
        $this->assertSame($response->gatewayId, $response->details['reference_code']);
        $this->assertSame($response->copyPasteUrl, $response->details['content']);
    }

    public function testItShouldCreateValidPaymentChargeWithFinalClasses()
    {
        $createPixChargeGateway = new Pay2MGateway(new Pay2MGatewayParams($_ENV['CLIENT_ID'], $_ENV['CLIENT_SECRET']));

        $response = $createPixChargeGateway->createCharge(new PixData(
            generator: new GeneratorData(name: 'Kilderson Sena', document: '02993405381'),
            value: 1,
        ));

        $this->assertIsArray($response);
        $this->assertNotEmpty($response['gatewayId']);
        $this->assertNotEmpty($response['details']);
        $this->assertNotEmpty($response['qrCode']);
        $this->assertIsArray($response['details']);
        $this->assertArrayHasKey('content', $response['details']);
        $this->assertArrayHasKey('reference_code', $response['details']);
        $this->assertSame($response['gatewayId'], $response['details']['reference_code']);
        $this->assertSame($response['copyPasteUrl'], $response['details']['content']);
    }
}
