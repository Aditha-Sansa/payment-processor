<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\ExchangeRateRepository;
use App\Repositories\PaymentImportRepository;
use App\Exchange\Providers\FrankfurterProvider;
use App\Repositories\ExchangeRateRepositoryInterface;
use App\Exchange\Contracts\ExchangeRateProviderInterface;
use App\Repositories\Contracts\PaymentImportRepositoryInterface;

class PaymentProcessingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentImportRepositoryInterface::class, PaymentImportRepository::class);

        $this->app->bind(ExchangeRateProviderInterface::class, function () {
            $provider = config('payments.exchange.provider', 'frankfurter');
            return match ($provider) {
                default => new FrankfurterProvider(),
            };
        });

        $this->app->bind(ExchangeRateRepositoryInterface::class, ExchangeRateRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
