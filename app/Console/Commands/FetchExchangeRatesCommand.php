<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use App\Repositories\Contracts\ExchangeRateRepositoryInterface;

class FetchExchangeRatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exchange-rates:fetch {--base=USD}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and store latest exchange rates (base USD by default).';

    /**
     * Execute the console command.
     */
    public function handle(ExchangeRateRepositoryInterface $exchangeRateRepository): int
    {
        $base = (string) $this->option('base');

        $rates = $exchangeRateRepository->refreshLatestRates($base);

        $this->info("Fetched " . count($rates) . " rates for base={$base}.");

        return self::SUCCESS;
    }

}
