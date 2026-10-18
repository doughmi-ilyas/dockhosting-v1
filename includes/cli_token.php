<?php
// CLI tokens are "<userId>_SECRET_<time>_<hmac>" base64'd, signed with APP_SECRET from .env.
// ponytail: no expiry/revocation; rotate APP_SECRET to invalidate every issued token.
require_once __DIR__ . '/../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__ . '/../')->safeLoad();

function cli_secret() {
    $secret = $_ENV['APP_SECRET'] ?? getenv('APP_SECRET');
    if (!$secret || strlen($secret) < 32) {
        http_response_code(500);
        exit('APP_SECRET (32+ chars) is not configured.');
    }
    return $secret;
}

function cli_token_create($userId) {
    $payload = (int)$userId . "_SECRET_" . time();
    return base64_encode($payload . "_" . hash_hmac('sha256', $payload, cli_secret()));
}

// Returns the user id the token was issued to, or null if it is forged or malformed.
function cli_token_user($token) {
    $decoded = base64_decode((string)$token, true);
    if ($decoded === false || !preg_match('/^(\d+)_SECRET_(\d+)_([a-f0-9]{64})$/', $decoded, $m)) {
        return null;
    }
    $expected = hash_hmac('sha256', $m[1] . "_SECRET_" . $m[2], cli_secret());
    return hash_equals($expected, $m[3]) ? (int)$m[1] : null;
}

if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
    $_ENV['APP_SECRET'] = str_repeat('k', 32);
    $t = cli_token_create(7);
    assert(cli_token_user($t) === 7);
    assert(cli_token_user(base64_encode("7_SECRET_" . time())) === null); // old unsigned format
    $forged = base64_encode(preg_replace('/^7_/', '1_', base64_decode($t)));
    assert(cli_token_user($forged) === null);
    echo "ok\n";
}
