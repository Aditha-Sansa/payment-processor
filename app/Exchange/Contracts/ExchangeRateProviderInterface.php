<?php

namespace App\Exchange\Contracts;

interface ExchangeRateProviderInterface
{
    public function fetchRates(?string $date, string $base): array;

    public function name(): string;
}
