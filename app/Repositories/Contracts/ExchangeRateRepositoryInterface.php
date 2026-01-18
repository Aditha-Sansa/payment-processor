<?php

namespace App\Repositories;

interface ExchangeRateRepositoryInterface
{
    public function getLatestRates(string $base, ?string $provider): array;

    public function refreshLatestRates(string $base, ?string $provider): array;
}
