<?php
/**
 * Optional AI provider configuration.
 * Prefer OPENAI_API_KEY as a server environment variable. For local XAMPP only,
 * copy ai.local.example.php to ai.local.php and add your own private key there.
 */
$local = __DIR__ . '/ai.local.php';
$localConfig = is_file($local) ? require $local : [];
if (!is_array($localConfig)) $localConfig = [];
return [
    'api_key' => (string)(getenv('OPENAI_API_KEY') ?: ($localConfig['api_key'] ?? '')),
    'model' => (string)(getenv('OPENAI_MODEL') ?: ($localConfig['model'] ?? 'gpt-6.2-astra')),
    'endpoint' => 'https://api.openai.com/v1/responses',
];
