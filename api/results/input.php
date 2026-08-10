<?php
/**
 * Reads the request body as JSON - this is what client.ts's apiRequest()
 * sends by default now that the mobile app talks to Render (no InfinityFree
 * WAF to dodge). Falls back to $_POST so curl -d / plain form posts still
 * work for manual testing.
 */
function skp_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw !== false && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }
    return $_POST;
}