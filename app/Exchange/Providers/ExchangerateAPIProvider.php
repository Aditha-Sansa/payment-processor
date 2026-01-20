<?php

namespace App\Exchange\Providers;

use Illuminate\Support\Facades\Http;
use App\Exchange\Contracts\ExchangeRateProviderInterface;

class ExchangerateAPIProvider implements ExchangeRateProviderInterface
{
    public function name(): string
    {
        return 'exchangerateapi';
    }

    public function fetchRates(?string $date, string $base): array
    {
        $url = 'https://v6.exchangerate-api.com/v6/' . config('payments.exchange.exchange_rate_com_key') . '/latest/' . $base;

        $response = Http::timeout(15)->retry(2, 300)->get($url)->throw()->json();

        return [
            'base' => (string) ($response['base_code'] ?? $base),
            'date' => (string) ($response['time_last_update_utc'] ?? now()->toDateString()),
            'rates' => isset($response['conversion_rates']) && is_array($response['conversion_rates'])
                ? array_map('floatval', $response['conversion_rates'])
                : []
        ];
    }
}
