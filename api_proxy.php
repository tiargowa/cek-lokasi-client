<?php
// api_proxy.php
// Simple proxy to perform Google Geocoding (reverse) on server-side so the API key
// never appears in client-side JS. Place your real API key in an environment variable
// named GOOGLE_MAPS_API_KEY or in a separate `secret_config.php` file (recommended
// to keep that file outside webroot).

header('Content-Type: application/json; charset=utf-8');

// --- Load configuration (API key, allowed origins, client token) ---
$google_key = getenv('GOOGLE_MAPS_API_KEY') ?: null;
$allowed_origins_raw = getenv('ALLOWED_ORIGINS') ?: null; // comma-separated
$client_token_config = getenv('CLIENT_TOKEN') ?: null; // optional static token
$token_signing_secret = getenv('TOKEN_SIGNING_SECRET') ?: null; // optional short-lived token signing secret
$token_issue_secret = getenv('TOKEN_ISSUE_SECRET') ?: null; // only used by token issuance endpoint

// If not set in environment, try to include local secret file (recommended to move outside webroot)
if (!$google_key || $allowed_origins_raw === null || $client_token_config === null) {
    $localSecret = __DIR__ . '/secret_config.php';
    if (file_exists($localSecret)) {
        include $localSecret; // may set $GOOGLE_MAPS_API_KEY, $ALLOWED_ORIGINS, $CLIENT_TOKEN, $TOKEN_SIGNING_SECRET, $TOKEN_ISSUE_SECRET
        if (empty($google_key) && !empty($GOOGLE_MAPS_API_KEY)) $google_key = $GOOGLE_MAPS_API_KEY;
        if ($allowed_origins_raw === null && !empty($ALLOWED_ORIGINS)) $allowed_origins_raw = $ALLOWED_ORIGINS;
        if ($client_token_config === null && !empty($CLIENT_TOKEN)) $client_token_config = $CLIENT_TOKEN;
        if (empty($token_signing_secret) && !empty($TOKEN_SIGNING_SECRET)) $token_signing_secret = $TOKEN_SIGNING_SECRET;
        if (empty($token_issue_secret) && !empty($TOKEN_ISSUE_SECRET)) $token_issue_secret = $TOKEN_ISSUE_SECRET;
    }
}

// Normalize allowed origins into array (if provided)
$allowed_origins = [];
if (!empty($allowed_origins_raw)) {
    $parts = preg_split('/\s*,\s*/', trim($allowed_origins_raw));
    foreach ($parts as $p) {
        if ($p !== '') $allowed_origins[] = $p;
    }
}

// Helper: get request origin from headers (Origin preferred, else Referer)
function get_request_origin() {
    // PHP getallheaders for Apache; fallback to $_SERVER
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $origin = null;
    if (!empty($headers['Origin'])) $origin = $headers['Origin'];
    elseif (!empty($headers['origin'])) $origin = $headers['origin'];
    else {
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
        if ($referer) {
            $u = parse_url($referer);
            if ($u && isset($u['scheme']) && isset($u['host'])) {
                $origin = $u['scheme'] . '://' . $u['host'];
                if (isset($u['port'])) $origin .= ':' . $u['port'];
            }
        }
    }
    return $origin;
}

// Helper: check if origin allowed (supports wildcard subdomain prefix like https://*.example.com)
function origin_is_allowed($origin, $allowed_list) {
    if (empty($allowed_list)) return true; // if no list provided, allow all (but not recommended)
    if (empty($origin)) return false;

    foreach ($allowed_list as $allowed) {
        // exact match
        if (strcasecmp($origin, $allowed) === 0) return true;

        // wildcard subdomain: https://*.example.com
        if (strpos($allowed, '*') !== false) {
            $pattern = '/^' . str_replace('\*', '.*', preg_quote($allowed, '/')) . '$/i';
            if (preg_match($pattern, $origin)) return true;
        }
    }
    return false;
}

$raw_input = file_get_contents('php://input');
$json_body = json_decode($raw_input, true);

// --- Security checks: Origin + optional client token ---
$origin = get_request_origin();
if (!origin_is_allowed($origin, $allowed_origins)) {
    http_response_code(403);
    echo json_encode(['error' => 'Origin not allowed', 'origin' => $origin]);
    exit;
}

// If client token (static) OR token signing secret configured, require a token
$token_required = (!empty($client_token_config) || !empty($token_signing_secret));
if ($token_required) {
    // read header
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $provided = null;
    if (!empty($headers['X-Client-Token'])) $provided = $headers['X-Client-Token'];
    elseif (!empty($headers['x-client-token'])) $provided = $headers['x-client-token'];

    // if not in header, try JSON body
    if ($provided === null && is_array($json_body) && !empty($json_body['client_token'])) $provided = $json_body['client_token'];

    $authorized = false;
    if ($provided !== null) {
        // 1) match static client token
        if (!empty($client_token_config) && hash_equals((string)$client_token_config, (string)$provided)) {
            $authorized = true;
        }

        // 2) verify short-lived signed token if signing secret available
        if (!$authorized && !empty($token_signing_secret)) {
            // token format: base64url(payload).base64url(sig)
            $parts = explode('.', $provided);
            if (count($parts) === 2) {
                list($b64payload, $b64sig) = $parts;
                // base64url decode
                $pad = strlen($b64payload) % 4;
                if ($pad) $b64payload .= str_repeat('=', 4 - $pad);
                $payload_json = base64_decode(strtr($b64payload, '-_', '+/'));
                $pad2 = strlen($b64sig) % 4;
                if ($pad2) $b64sig .= str_repeat('=', 4 - $pad2);
                $sig = base64_decode(strtr($b64sig, '-_', '+/'));
                if ($payload_json !== false && $sig !== false) {
                    $computed = hash_hmac('sha256', $payload_json, $token_signing_secret, true);
                    if (hash_equals($computed, $sig)) {
                        $payload = json_decode($payload_json, true);
                        if (is_array($payload) && isset($payload['exp']) && $payload['exp'] >= time()) {
                            // optional: check origin claim matches request origin
                            if (empty($payload['origin']) || $payload['origin'] === $origin) {
                                $authorized = true;
                            }
                        }
                    }
                }
            }
        }
    }

    if (!$authorized) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized: invalid or missing client token']);
        exit;
    }
}

// If origin is allowed, set CORS header to allow client to read response
if (!empty($origin)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Client-Token');
}

// --- Read input ---
// Use parsed JSON body from earlier
$data = is_array($json_body) ? $json_body : null;
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

// Minimal action whitelist to avoid open proxy
if (empty($data['action']) || $data['action'] !== 'geocode') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
    exit;
}

$lat = isset($data['lat']) ? filter_var($data['lat'], FILTER_VALIDATE_FLOAT) : false;
$lng = isset($data['lng']) ? filter_var($data['lng'], FILTER_VALIDATE_FLOAT) : false;
if ($lat === false || $lng === false) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid lat/lng values']);
    exit;
}

if (empty($google_key)) {
    http_response_code(500);
    echo json_encode(['error' => 'Server misconfiguration: Google API key not set.']);
    exit;
}

$url = 'https://maps.googleapis.com/maps/api/geocode/json?latlng=' . urlencode($lat . ',' . $lng) . '&key=' . urlencode($google_key);

// cURL request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$resp = curl_exec($ch);
$curlErr = curl_error($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($resp === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Failed to fetch from Google', 'detail' => $curlErr]);
    exit;
}

http_response_code($statusCode ?: 200);
// Relay the raw Google response (already JSON)
echo $resp;
exit;
