<?php

namespace App\Services;

interface ProviderInterface
{
    /**
     * Fetch available countries.
     */
    public function getCountries(): array;

    /**
     * Fetch virtual phone number for a specific country.
     *
     * @param string $countryCode
     */
    public function getNumberByCountry(string $countryCode): array;

    /**
     * Fetch SMS history for a given number to check for OTP.
     *
     * @param string $countryCode
     * @param string $phoneNumber
     */
    public function checkSmsHistory(string $countryCode, string $phoneNumber): array;
}
