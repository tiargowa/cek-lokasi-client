<?php
// secret_config.example.php
// COPY THIS FILE to secret_config.php and set the value. Better: keep the
// real secret_config.php outside the webroot (e.g. c:/wamp64/secrets/secret_config.php)
// and include it from api_proxy.php, or set an environment variable.

// Example:
// $GOOGLE_MAPS_API_KEY = 'YOUR_REAL_GOOGLE_MAPS_API_KEY_HERE';

// Optional additional configuration:
// $ALLOWED_ORIGINS = 'http://localhost:80, https://yourdomain.com';
// $CLIENT_TOKEN = 'static-client-token-if-you-want'; // optional static token
// For short-lived signed tokens (recommended over static token):
// $TOKEN_SIGNING_SECRET = 'a-long-random-signing-secret';
// $TOKEN_ISSUE_SECRET = 'a-secret-used-to-authenticate-token-issuers';

// Do NOT commit `secret_config.php` with the real key to version control.
