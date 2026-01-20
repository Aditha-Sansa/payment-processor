<?php

namespace App\Services;

use Illuminate\Support\Carbon;

class PaymentRowValidatorService
{
    public function validate(array $rowAssoc): array
    {
        $errors = [];

        $email = trim((string) ($rowAssoc['customer_email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email';
        }

        $currency = strtoupper(trim((string) ($rowAssoc['currency'] ?? '')));
        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            $errors[] = 'Invalid currency';
        }
        $amountRaw = trim((string) ($rowAssoc['amount'] ?? ''));
        $amount = $this->parseAmount($amountRaw);

        if ($amount === null) {
            $errors[] = 'Invalid amount';
        } elseif ($amount < 0) {
            $errors[] = 'Amount cannot be negative';
        }

        $reference = trim((string) ($rowAssoc['reference_no'] ?? ''));
        if ($reference === '' || strlen($reference) > 64) {
            $errors[] = 'Invalid reference_no';
        }

        $paidAtRaw = trim((string) ($rowAssoc['date_time'] ?? ''));
        $paidAt = null;
        if ($paidAtRaw !== '') {
            try {
                $paidAt = Carbon::parse($paidAtRaw);
            } catch (\Throwable) {
                $errors[] = 'Invalid date_time';
            }
        }

        if ($errors) {
            return ['ok' => false, 'errors' => $errors];
        }

        return [
            'ok' => true,
            'normalized' => [
                'customer_id' => trim((string) ($rowAssoc['customer_id'] ?? '')),
                'customer_name' => trim((string) ($rowAssoc['customer_name'] ?? '')),
                'customer_email' => $email,
                'original_amount' => $amount,
                'currency' => $currency,
                'reference_no' => $reference,
                'paid_at' => $paidAt?->toDateTimeString(),
            ],
        ];
    }

    private function parseAmount(string $value): ?float
    {
        if ($value === '')
            return null;

        // Handle both delimeter situations "2,345.67" and "2.345,67"
        $hasDot = str_contains($value, '.');
        $hasComma = str_contains($value, ',');

        $normalized = $value;

        if ($hasDot && $hasComma) {
            // if there is EU style amounts "2.345,67" with dot thousands, comma decimal
            // reemove dots
            $normalized = str_replace('.', '', $normalized);
            // replace comma with dot
            $normalized = str_replace(',', '.', $normalized);
        }
        if ($hasComma && !$hasDot) {
            // comma decimal situation "123,45" 
            $normalized = str_replace(',', '.', $normalized);
        }
        if (!is_numeric($normalized))
            return null;

        return (float) $normalized;
    }
}
