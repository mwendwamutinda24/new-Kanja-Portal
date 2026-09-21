<?php
// ============================================================
// africa_talking_sms.php
// Minimal Africa's Talking SMS sender (plain cURL, no SDK/Composer).
//
// Credentials are read from environment variables so this file
// is safe to commit to Git and deploy to Render:
//
//   AT_USERNAME   -> your AT username (use "sandbox" for testing)
//   AT_API_KEY    -> your Africa's Talking API key
//   AT_SENDER_ID  -> optional alphanumeric sender ID / short code
//   AT_SANDBOX    -> "1" (default) to use sandbox, "0" for production
//
// On Render: Dashboard -> your service -> Environment -> Add Env Vars.
// Locally: you can create a small at_config.php that defines these
// constants BEFORE this file is included (see bottom of file for
// backward compatibility).
// ============================================================

// ─── Backwards compatibility ────────────────────────────────
// If the old at_config.php still exists on the server, load it.
// It must only define constants (AT_USERNAME, AT_API_KEY, ...)
// — never echo anything, never output whitespace, never call
// header(). If it's missing, we just fall through to env vars.
$__at_config = __DIR__ . '/at_config.php';
if (file_exists($__at_config)) {
    require_once $__at_config;
}

// ─── Resolve config from constants, then env vars ──────────
if (!defined('AT_USERNAME')) {
    define('AT_USERNAME', getenv('AT_USERNAME') ?: 'sandbox');
}
if (!defined('AT_API_KEY')) {
    define('AT_API_KEY', getenv('AT_API_KEY') ?: '');
}
if (!defined('AT_SENDER_ID')) {
    define('AT_SENDER_ID', getenv('AT_SENDER_ID') ?: '');
}
if (!defined('AT_SANDBOX')) {
    // Accept "1"/"true"/"yes" from env, otherwise default to true.
    $sandboxEnv = getenv('AT_SANDBOX');
    $sandbox = true;
    if ($sandboxEnv !== false && $sandboxEnv !== '') {
        $sandbox = in_array(strtolower((string)$sandboxEnv), ['1', 'true', 'yes', 'on'], true);
    }
    define('AT_SANDBOX', $sandbox);
}

/**
 * Normalize a Kenyan phone number to the +2547XXXXXXXX / +2541XXXXXXXX
 * format Africa's Talking expects. Returns null if it doesn't look
 * like a valid number.
 */
function normalize_kenyan_phone($raw) {
    if ($raw === null) return null;
    $digits = preg_replace('/[^0-9+]/', '', trim((string)$raw));

    if ($digits === '') return null;

    // Already in +254... form
    if (strpos($digits, '+254') === 0 && strlen($digits) === 13) {
        return $digits;
    }
    // 254... form (no plus)
    if (strpos($digits, '254') === 0 && strlen($digits) === 12) {
        return '+' . $digits;
    }
    // Local 07... / 01... form
    if (strpos($digits, '0') === 0 && strlen($digits) === 10) {
        return '+254' . substr($digits, 1);
    }
    // Bare 7XXXXXXXX / 1XXXXXXXX (9 digits, no leading 0)
    if (strlen($digits) === 9 && ($digits[0] === '7' || $digits[0] === '1')) {
        return '+254' . $digits;
    }

    return null; // doesn't match a recognizable Kenyan mobile format
}

/**
 * Send a single SMS via Africa's Talking.
 * Returns ['success' => bool, 'response' => string (raw/decoded info)]
 */
function send_africas_talking_sms($toPhone, $message) {
    if (AT_API_KEY === '') {
        return array(
            'success'  => false,
            'response' => 'AT_API_KEY is not configured on the server.',
        );
    }

    $url = AT_SANDBOX
        ? 'https://api.sandbox.africastalking.com/version1/messaging'
        : 'https://api.africastalking.com/version1/messaging';

    $postFields = array(
        'username' => AT_USERNAME,
        'to'       => $toPhone,
        'message'  => $message,
    );
    if (AT_SENDER_ID !== '') {
        $postFields['from'] = AT_SENDER_ID;
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json',
        'apiKey: ' . AT_API_KEY,
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

    $responseBody = curl_exec($ch);
    $curlError    = curl_error($ch);
    $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($responseBody === false) {
        return array('success' => false, 'response' => 'cURL error: ' . $curlError);
    }

    $decoded = json_decode($responseBody, true);
    if ($httpCode >= 200 && $httpCode < 300
        && isset($decoded['SMSMessageData']['Recipients'][0]['status'])) {

        $status = $decoded['SMSMessageData']['Recipients'][0]['status'];
        // Africa's Talking reports per-recipient status even on a 2xx HTTP response
        if (strtolower($status) === 'success') {
            return array('success' => true, 'response' => $responseBody);
        }
        return array('success' => false, 'response' => $responseBody);
    }

    return array(
        'success'  => false,
        'response' => 'HTTP ' . $httpCode . ': ' . $responseBody,
    );
}
