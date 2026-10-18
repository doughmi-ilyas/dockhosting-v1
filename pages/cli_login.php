<?php
session_start();
require_once '../php/connect.php';
require_once '../includes/cli_token.php';
if(!isset($_SESSION["id"]))
{
    header("location: ../login.php");
    exit();
}

// Must be a bare port: anything else (e.g. "80@evil.com") would send the token off-host.
$port = filter_var($_GET["port"] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
if ($port === false) {
    http_response_code(400);
    exit('Invalid port');
}

$query = http_build_query(['token' => cli_token_create($_SESSION["id"]), 'username' => $_SESSION["username"]]);

header("Location: http://localhost:$port/callback?$query");
exit;
