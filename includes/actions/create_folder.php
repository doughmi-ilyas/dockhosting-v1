<?php
session_start();

if (!isset($_SESSION["id"]) || $_SERVER["REQUEST_METHOD"] != "POST") {
    header("location: ../../index.php");
    exit;
}

$container_name = $_POST["container"] ?? "";
$path_rel = $_POST["path"] ?? "";
$folder_name = $_POST["name"] ?? "";

if (empty($container_name) || empty($folder_name)) {
    header("location: ../../pages/dashboard.php");
    exit;
}

require_once "../../php/connect.php";
require_once "../../Classes/Project.php";

$full_path = (new Project)->resolvePath($container_name, $path_rel . "/" . $folder_name, false);

if ($full_path === false) {
    header("location: ../../pages/file-manager.php?container=" . urlencode($container_name) . "&error=invalid_path");
    exit;
}

if (!file_exists($full_path)) {
    mkdir($full_path, 0777, true);
}

header("location: ../../pages/file-manager.php?container=" . $container_name . "&path=" . $path_rel);
exit;
