<?php 
// ----- CONFIGURACIÓN DE BASE DE DATOS (Lee de ENV o usa valores por defecto) -----
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'viajego_db'); 
define('DB_USER', getenv('DB_USER') ?: 'root');
// Usamos el operador ternario ?: para fallback seguro
define('DB_PASS', getenv('DB_PASS') ?: ''); 
// --------------------------------------------------------------------------------

// 💸 TARIFAS GLOBALES (Usadas en reservation_logic.php)
define('ADULT_FEE', 500.00);
define('CHILD_FEE', 300.00);

// 🌐 BASE_URL (FINAL: Ruta que garantiza que los estilos y scripts se carguen)
define('BASE_URL', 'http://localhost/viajego3/');

// Rutas relativas internas
define('ASSETS_PATH', BASE_URL . 'assets/');
define('ROOT_PATH', __DIR__ . '/../../');

// ---------------------------------------------
// CONEXIÓN PDO CON SINGLETON
// ---------------------------------------------
require_once __DIR__ . '/Database.php';

// Crear instancia Singleton y obtener PDO
$pdo = Database::getInstance()->getConnection();

// ---------------------------------------------
// ACTIVAR SESSION HANDLER
// ---------------------------------------------
require_once __DIR__ . '/session_handler.php';

// Registrar manejador usando el PDO ya creado
$handler = new MySQLSessionHandler($pdo, 3600);
session_set_save_handler($handler, true);

// Configuración del GC
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);

// Iniciar la sesión si aún no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------------------------------------------
// HELPERS
// ---------------------------------------------
function redirect(string $location)
{
    header("Location: " . BASE_URL . $location);
    exit;
}

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>