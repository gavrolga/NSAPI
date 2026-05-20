<?php

namespace App\Services\Gateways;

readonly class GatewayResult
{
    public function __construct(
        public bool    $success,
        public ?string $error = null,
    ) {}

    public static function ok(): self
    {
        return new self(success: true);
    }

    public static function fail(string $error): self
    {
        return new self(success: false, error: $error);
    }
}
