<?php
session_start();

if (!isset($_SESSION["id"])) {
    exit;
}

require_once "../../php/connect.php";
require_once "../../Classes/Project.php";

$target_file = (new Project)->resolvePath($_GET["container"] ?? "", $_GET["file"] ?? "");

if ($target_file !== false && is_file($target_file)) {
    $mime = mime_content_type($target_file);
    header("Content-Type: $mime");
    // User files (SVG/HTML) must not run script on the panel's origin.
    header("X-Content-Type-Options: nosniff");
    header("Content-Security-Policy: sandbox");
    readfile($target_file);
} else {
    echo "File not found";
}
