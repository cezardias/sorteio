<?php

declare(strict_types=1);

namespace AstrotechLabs\Pay2MSdk\CreatePixCharge\Dto;

final class GeneratorData
{
    public function __construct(
        public readonly string $name,
        public readonly string $document
    ) {
    }

    public function values(): array
    {
        $values = get_object_vars($this);
        array_walk($values, fn (&$value, $property) => $value = $this->get($property));
        return $values;
    }

    public function get(string $property): mixed
    {
        $getter = "get" . ucfirst($property);

        if (method_exists($this, $getter)) {
            return $this->{$getter}();
        }

        return $this->{$property};
    }

    public function getBillingType(): string
    {
        return $this->billingType->value;
    }
}
