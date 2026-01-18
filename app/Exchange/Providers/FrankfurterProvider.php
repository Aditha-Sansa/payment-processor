<?php

namespace App\Exchange\Providers;

use Illuminate\Support\Facades\Http;
use App\Exchange\Contracts\ExchangeRateProviderInterface;

class FrankfurterProvider implements ExchangeRateProviderInterface
{
    public function name(): string
    {
        return 'frankfurter';
    }

    public function fetchRates(?string $date, string $base): array
    {
        // Frankfurter "latest" endpoint
        $url = 'https://api.frankfurter.dev/v1/latest';

        $response = Http::timeout(15)->retry(2, 300)->get($url, [
            'base' => $base,
        ])->throw()->json();

        // Expected: {amount:1, base:"USD", date:"YYYY-MM-DD", rates:{...}}
        return [
            'base' => (string) ($response['base'] ?? $base),
            'date' => (string) ($response['date'] ?? now()->toDateString()),
            'rates' => array_map('floatval', $response['rates'] ?? []),
        ];
    }
}
