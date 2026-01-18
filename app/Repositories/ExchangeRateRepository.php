<?php

namespace App\Repositories;

use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Cache;
use App\Exchange\Contracts\ExchangeRateProviderInterface;
use App\Repositories\Contracts\ExchangeRateRepositoryInterface;

class ExchangeRateRepository implements ExchangeRateRepositoryInterface
{
    public function __construct(
        private readonly ExchangeRateProviderInterface $provider
    ) {
    }

    public function getLatestRates(string $base = 'USD', ?string $provider = null): array
    {
        $providerName = $provider ?: $this->provider->name();
        $today = now()->toDateString();
        $cacheKey = "exrates:{$providerName}:{$base}:{$today}";

        $ttl = config('payments.exchange.cache_ttl_seconds', 3600);

        return Cache::store('redis')->remember($cacheKey, $ttl, function () use ($providerName, $base, $today) {
            $existing = ExchangeRate::query()
                ->where('provider', $providerName)
                ->where('base', $base)
                ->where('date', $today)
                ->latest('fetched_at')
                ->first();

            if ($existing) {
                return $existing->rates ?? [];
            }

            $payload = $this->provider->fetchRates(null, $base);

            ExchangeRate::query()->create([
                'provider' => $providerName,
                'base' => $base,
                'date' => $today,
                'rates' => $payload['rates'],
                'fetched_at' => now(),
            ]);

            return $payload['rates'];
        });
    }

    public function refreshLatestRates(string $base = 'USD', ?string $provider = null): array
    {
        $providerName = $provider ?: $this->provider->name();
        $today = now()->toDateString();

        $payload = $this->provider->fetchRates(null, $base);

        ExchangeRate::query()->updateOrCreate(
            ['provider' => $providerName, 'base' => $base, 'date' => $today],
            ['rates' => $payload['rates'], 'fetched_at' => now()]
        );

        Cache::store('redis')->put(
            "exrates:{$providerName}:{$base}:{$today}",
            $payload['rates'],
            config('payments.exchange.cache_ttl_seconds', 3600)
        );

        return $payload['rates'];
    }
}
