<?php

namespace App\Traits;

trait SanitizesCardData
{
    /**
     * Sanitize payment details to remove sensitive cardholder data.
     * PCI-DSS Compliant: Never store full PAN or CVV.
     *
     * @param array|null $data
     * @return array
     */
    protected function sanitizePaymentDetails($data): array
    {
        // Handle null or non-array data
        if (!is_array($data)) {
            return [];
        }

        $sanitized = $data;

        // Remove sensitive fields (PCI-DSS Requirement 3.4)
        unset(
            $sanitized['card_number'],
            $sanitized['cvv'],
            $sanitized['cvv2'],
            $sanitized['pin'],
            $sanitized['card_pin'],
            $sanitized['card_cvv'],
            $sanitized['security_code'],
            $sanitized['card_security_code']
        );

        // Keep only last 4 digits if card number was provided (PCI-DSS Requirement 3.4)
        if (isset($data['card_number']) && is_string($data['card_number'])) {
            $cardNumber = preg_replace('/\s+/', '', $data['card_number']);
            if (strlen($cardNumber) >= 4) {
                $sanitized['last4'] = substr($cardNumber, -4);
            }
        }

        // Recursively sanitize nested arrays
        foreach ($sanitized as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizePaymentDetails($value);
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize data for logging purposes.
     * Ensures no card data is logged (PCI-DSS Requirement 10.5.4).
     *
     * @param array $data
     * @return array
     */
    protected function sanitizeForLogging(array $data): array
    {
        $sanitized = $this->sanitizePaymentDetails($data);

        // Additional sanitization for logging
        // Mask any potential card numbers in strings
        foreach ($sanitized as $key => $value) {
            if (is_string($value)) {
                // Mask potential card numbers (13-19 digits)
                $sanitized[$key] = preg_replace(
                    '/\b\d{13,19}\b/',
                    '****-****-****-****',
                    $value
                );
            }
        }

        return $sanitized;
    }

    /**
     * Mask card number for display (shows only last 4 digits).
     *
     * @param string|null $cardNumber
     * @return string|null
     */
    protected function maskCardNumber(?string $cardNumber): ?string
    {
        if (!$cardNumber) {
            return null;
        }

        $cleaned = preg_replace('/\s+/', '', $cardNumber);
        $length = strlen($cleaned);

        if ($length < 4) {
            return '****';
        }

        $last4 = substr($cleaned, -4);
        $masked = str_repeat('*', max(0, $length - 4));

        return $masked . $last4;
    }
}

