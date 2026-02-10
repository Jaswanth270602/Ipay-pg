<?php
/**
 * Badlicash Payment Gateway - Webhook Receiver
 * 
 * This file receives webhook events from the payment gateway
 * and logs them for monitoring and debugging.
 */

header('Content-Type: application/json');

// Log file path
$logFile = __DIR__ . '/logs/webhooks.log';
$logDir = dirname($logFile);

// Ensure logs directory exists
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Get webhook payload
$rawPayload = file_get_contents('php://input');
$headers = getallheaders();

// Parse JSON payload
$payload = json_decode($rawPayload, true);

// Extract data from nested payload structure
$transactionId = $payload['transaction_id'] 
    ?? $payload['data']['transaction_id'] 
    ?? $payload['payload']['payment']['entity']['id'] 
    ?? null;

$orderId = $payload['order_id'] 
    ?? $payload['data']['order_id'] 
    ?? $payload['payload']['payment']['entity']['order_id']
    ?? $payload['payload']['order']['entity']['id']
    ?? null;

$status = $payload['status'] 
    ?? $payload['data']['status'] 
    ?? $payload['payload']['payment']['entity']['status']
    ?? null;

$amount = $payload['amount'] 
    ?? $payload['data']['amount'] 
    ?? $payload['payload']['payment']['entity']['amount']
    ?? null;

// Generate log entry
$logEntry = [
    'timestamp' => date('Y-m-d H:i:s'),
    'datetime' => date('c'),
    'event_type' => $payload['event_type'] ?? $payload['event'] ?? 'unknown',
    'transaction_id' => $transactionId,
    'order_id' => $orderId,
    'status' => $status,
    'amount' => $amount,
    'payload' => $payload,
    'headers' => $headers,
    'signature' => $headers['X-Badlicash-Signature'] ?? $headers['X-BadliCash-Signature'] ?? null,
];

// Format log entry
$logLine = json_encode($logEntry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n" . str_repeat('-', 80) . "\n";

// Write to log file
file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);

// Also log to a JSON file for easier parsing
$jsonLogFile = __DIR__ . '/logs/webhooks.json';
$existingLogs = [];

if (file_exists($jsonLogFile)) {
    $existingContent = file_get_contents($jsonLogFile);
    $existingLogs = json_decode($existingContent, true) ?? [];
}

// Add new entry to beginning (most recent first)
array_unshift($existingLogs, $logEntry);

// Keep only last 100 events
$existingLogs = array_slice($existingLogs, 0, 100);

// Write updated logs
file_put_contents($jsonLogFile, json_encode($existingLogs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

// Log to PHP error log for debugging
error_log("Webhook received: " . ($logEntry['event_type'] ?? 'unknown') . " - TXN: " . ($logEntry['transaction_id'] ?? 'N/A'));

// Return success response
http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Webhook received and logged',
    'event_type' => $logEntry['event_type'],
    'timestamp' => $logEntry['timestamp'],
]);

