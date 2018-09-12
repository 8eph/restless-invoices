<?php

namespace App\Service;

class ExchangeRateService
{
    /** @var array */
    private $exchangeRates;

    public function __construct()
    {
        // intentionally hard-coded and we're not checking for errors, caching or providing fallback providers/handlers
        // ideally we'd want to store historical values on the database for easy cross-referencing without external calls
        $this->exchangeRates = json_decode(file_get_contents('https://api.exchangeratesapi.io/latest'), true)['rates'];
    }

    public function toEuro($amount, string $sourceCurrency): float
    {
        if ('EUR' === $sourceCurrency) return $amount;

        // explicitly rounding up (it's the default anyway)
        // http://www.evro.si/en/info-for-consumers/rounding-rules/index.html
        return round($amount / $this->exchangeRates[$sourceCurrency], 2, PHP_ROUND_HALF_UP);
    }
}