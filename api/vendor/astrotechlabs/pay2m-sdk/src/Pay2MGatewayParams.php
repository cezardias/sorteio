<?php

declare(strict_types=1);

namespace AstrotechLabs\Pay2MSdk;

class Pay2MGatewayParams
{
    public function __construct(
        public readonly string $clientId,
        public readonly string $clientSecret
    ) {
    }
}
