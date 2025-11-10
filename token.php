<?php
// token.php
// Simple endpoint to issue short-lived HMAC-signed tokens for client use.
// Protect this endpoint with a server-side secret (TOKEN_ISSUE_SECRET).

header('Content-Type: application/json; charset=utf-8');

$issue_secret = getenv('TOKEN_ISSUE_SECRET') ?: null;
$signing_secret = getenv('TOKEN_SIGNING_SECRET') ?: null;

$localSecret = __DIR__ . '/secret_config.php';
if (file_exists($localSecret)) {
    include $localSecret; // may set $TOKEN_ISSUE_SECRET, $TOKEN_SIGNING_SECRET
    if (empty($issue_secret) && !empty($TOKEN_ISSUE_SECRET)) $issue_secret = $TOKEN_ISSUE_SECRET;
    if (empty($signing_secret) && !empty($TOKEN_SIGNING_SECRET)) $signing_secret = $TOKEN_SIGNING_SECRET;
}

if (empty($issue_secret) || empty($signing_secret)) {
    http_response_code(500);
    echo json_encode(['error' => 'Server misconfiguration: token secrets not configured']);
    exit;
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

// Require the admin issue secret to be provided to request token
if (empty($body['issue_secret']) || !hash_equals((string)$issue_secret, (string)$body['issue_secret'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized: invalid issue secret']);
    exit;
}

$ttl = isset($body['ttl']) ? intval($body['ttl']) : 300; // default 5 minutes
if ($ttl <= 0 || $ttl > 3600) $ttl = 300; // clamp to reasonable bounds

$now = time();
$payload = ['exp' => $now + $ttl, 'iat' => $now];
if (!empty($body['origin'])) $payload['origin'] = $body['origin'];

// base64url encode
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// sign
$payload_json = json_encode($payload);
$sig = hash_hmac('sha256', $payload_json, $signing_secret, true);
$token = base64url_encode($payload_json) . '.' . base64url_encode($sig);

echo json_encode(['token' => $token, 'expires_at' => $payload['exp']]);
exit;
