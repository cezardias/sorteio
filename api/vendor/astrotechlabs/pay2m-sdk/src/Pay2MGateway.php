<?php

declare(strict_types=1);

namespace AstrotechLabs\Pay2MSdk;

use AstrotechLabs\Pay2MSdk\CreatePixCharge\Dto\PixData;
use AstrotechLabs\Pay2MSdk\CreatePixCharge\CreatePixChargeGateway;

class Pay2MGateway
{
    public function __construct(
        private readonly Pay2MGatewayParams $params
    ) {
    }

    public function createCharge(PixData $pixData, bool $manualToken = false, ?string $token = null): array
    {
        $createPixChargeGateway = new CreatePixChargeGateway(
            clientId: $this->params->clientId,
            clientSecret: $this->params->clientSecret
        );

        return $createPixChargeGateway->createCharge(
            $pixData,
            $manualToken,
            $token
        )->toArray();
    }

    public function getAuthToken(): string
    {
        $createPixChargeGateway = new CreatePixChargeGateway(
            clientId: $this->params->clientId,
            clientSecret: $this->params->clientSecret
        );

        return $createPixChargeGateway->authenticate();
    }
}
