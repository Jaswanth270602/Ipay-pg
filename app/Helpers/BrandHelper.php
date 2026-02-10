<?php

if (!function_exists('brand_name')) {
    /**
     * Get the brand/company name from configuration.
     * This allows easy rebranding by changing APP_NAME in .env
     *
     * @return string
     */
    function brand_name(): string
    {
        return config('app.name', 'Payment Gateway');
    }
}

if (!function_exists('logo_path')) {
    /**
     * Get the logo path dynamically based on APP_NAME from .env
     * Logo file naming convention: {APP_NAME}_logo.png
     * 
     * Priority order:
     * 1. Try APP_NAME-based logos (multiple case variations)
     * 2. Fallback to existing "Badilicash_logo.png" (if exists)
     * 3. Fallback to generic "logo.png"
     * 4. Return dynamic path (file might be added later)
     *
     * Example: If APP_NAME="BadiliCash", will try:
     * - BadiliCash_logo.png
     * - badilicash_logo.png
     * - Badilicash_logo.png
     * - Badilicash_logo.png (existing file as fallback)
     * - logo.png (generic fallback)
     *
     * @return string
     */
    function logo_path(): string
    {
        $appName = config('app.name', 'PaymentGateway');
        
        // First, try with hyphens preserved (for names like "Payment Gateway" -> "Payment-gateway_logo.png")
        $hyphenName = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $appName)); // Keep spaces
        $hyphenName = preg_replace('/\s+/', '-', trim($hyphenName)); // Convert spaces to hyphens
        
        // Try hyphenated version first
        $hyphenLogo = 'images/logo/' . $hyphenName . '_logo.png';
        if (file_exists(public_path($hyphenLogo))) {
            return $hyphenLogo;
        }
        
        // Remove special chars and spaces, keep alphanumeric only
        $sanitizedName = preg_replace('/[^a-zA-Z0-9]/', '', $appName);
        
        // Try multiple naming variations based on APP_NAME
        $variations = [
            $sanitizedName . '_logo.png',              // Original case: "BadiliCash_logo.png"
            strtolower($sanitizedName) . '_logo.png',  // Lowercase: "badilicash_logo.png"
            ucfirst(strtolower($sanitizedName)) . '_logo.png', // First capital: "Badilicash_logo.png"
        ];
        
        // Try each APP_NAME variation
        foreach ($variations as $logoFilename) {
            $logoPath = 'images/logo/' . $logoFilename;
            if (file_exists(public_path($logoPath))) {
                return $logoPath;
            }
        }
        
        // Try "Payment-gateway_logo.png" specifically (if APP_NAME contains "Payment Gateway" or similar)
        if (stripos($appName, 'payment') !== false && stripos($appName, 'gateway') !== false) {
            $paymentGatewayLogo = 'images/logo/Payment-gateway_logo.png';
            if (file_exists(public_path($paymentGatewayLogo))) {
                return $paymentGatewayLogo;
            }
        }
        
        // Fallback to existing Badilicash logo if it exists (for backward compatibility)
        $existingLogo = 'images/logo/Badilicash_logo.png';
        if (file_exists(public_path($existingLogo))) {
            return $existingLogo;
        }
        
        // Final fallback to generic logo.png
        $genericLogo = 'images/logo/logo.png';
        if (file_exists(public_path($genericLogo))) {
            return $genericLogo;
        }
        
        // Return the Payment-gateway logo if it exists
        $paymentGatewayLogo = 'images/logo/Payment-gateway_logo.png';
        if (file_exists(public_path($paymentGatewayLogo))) {
            return $paymentGatewayLogo;
        }
        
        // Return the first variation as default (file might be added later)
        // This ensures the path is always valid even if file doesn't exist yet
        return 'images/logo/' . $variations[1]; // Use lowercase variation as default
    }
}

