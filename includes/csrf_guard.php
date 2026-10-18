<?php
// Prepended to every request via auto_prepend_file (see Dockerfile).
// Blocks cross-site POSTs. User apps live on *.dockhosting.dev, which browsers treat as
// same-site, so the SameSite=Lax session cookie alone doesn't stop them.
// No Origin/Referer at all (the CLI) is allowed through; browsers always send one on POST.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $source = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? null;
    $host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
    if ($source !== null && strtolower((string)parse_url($source, PHP_URL_HOST)) !== $host) {
        http_response_code(403);
        exit('Cross-site request blocked.');
    }
}
