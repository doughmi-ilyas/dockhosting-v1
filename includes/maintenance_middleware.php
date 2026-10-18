<?php
require_once __DIR__ . '/../Classes/Admin.php';

function checkMaintenance() {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '';
    if (strpos($uri, '/admin/') !== false ||
        strpos($uri, 'login.php') !== false ||
        strpos($uri, 'admin_actions.php') !== false) {
        return;
    }

    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        return;
    }

    $admin = new Admin();
    return $admin->isMaintenanceMode();
}
?>
