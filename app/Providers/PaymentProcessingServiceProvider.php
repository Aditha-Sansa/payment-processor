<?php

namespace App\Providers;

use App\Repositories\PaymentRepository;
use Illuminate\Support\ServiceProvider;
use App\Repositories\ExchangeRateRepository;
use App\Repositories\PaymentImportRepository;
use App\Exchange\Providers\FrankfurterProvider;
use App\Exchange\Contracts\ExchangeRateProviderInterface;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Repositories\Contracts\ExchangeRateRepositoryInterface;
use App\Repositories\Contracts\PaymentImportRepositoryInterface;


class PaymentProcessingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentImportRepositoryInterface::class, PaymentImportRepository::class);
        $this->app->bind(PaymentRepositoryInterface::class, PaymentRepository::class);

        $this->app->bind(ExchangeRateProviderInterface::class, function () {
            $provider = config('payments.exchange.provider', 'frankfurter');
            return match ($provider) {
                'exchangerateapi' => new \App\Exchange\Providers\ExchangerateAPIProvider(),
                // I noticed some currencies rates are missing in Frankfurter api below, but included anyway for testing
                // will be using exchangerateapi in production.
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
