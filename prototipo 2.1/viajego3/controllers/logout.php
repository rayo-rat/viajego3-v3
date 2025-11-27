<?php
// controllers/logout.php
require_once __DIR__ . '/../models/includes/config.php';
require_once __DIR__ . '/../models/includes/auth.php';

// Limpiar completamente la sesión
$_SESSION = array();

// Eliminar cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"], 
        $params["secure"], $params["httponly"]
    );
}

// Destruir sesión
session_destroy();

// Redirigir correctamente
header("Location: " . BASE_URL . "views/index.php?logout=success");
exit;