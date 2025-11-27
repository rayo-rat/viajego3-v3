<?php
// models/includes/auth.php
// Funciones de autenticación y autorización.

if (!isset($pdo)) {
    require_once __DIR__ . '/config.php';
}

/**
 * getPDO - Helper para obtener PDO desde Singleton
 */
function getPDO(): PDO
{
    return Database::getInstance()->getConnection();
}

/**
 * isLoggedIn
 */
function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

/**
 * isAdmin - revisa rol en SESSION
 */
function isAdmin(): bool
{
    return !empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * requireLogin - redirige a la vista index si no está autenticado
 */
function requireLogin()
{
    if (!isLoggedIn()) {
        redirect('views/index.php');
    }
}

/**
 * requireAdmin - si no es admin redirige
 */
function requireAdmin()
{
    if (!isAdmin()) {
        redirect('views/index.php');
    }
}

/**
 * loginUser - intenta autenticar
 */
function loginUser(string $email, string $password): array
{
    $pdo = getPDO(); // Usando Singleton

    $sql = "SELECT id, username, email, password_hash, role FROM users WHERE email = :email LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Email o contraseña incorrecta.'];
    }

    // Guardar en sesión
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'] ?? 'user';

    return ['success' => true, 'message' => 'Inicio de sesión correcto.'];
}

/**
 * registerUser - registra usuario básico con rol 'user'
 */
function registerUser(array $data): array
{
    $pdo = getPDO(); // Usando Singleton

    $username = trim($data['username'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if ($username === '' || $email === '' || $password === '') {
        return ['success' => false, 'message' => 'Faltan campos obligatorios.'];
    }

    // Verificar email único
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'El correo ya está registrado.'];
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, created_at) VALUES (:username, :email, :password_hash, 'user', NOW())");
    try {
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password_hash' => $password_hash
        ]);
        return ['success' => true, 'message' => 'Registro exitoso. Ya puedes iniciar sesión.'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error al registrar: ' . $e->getMessage()];
    }
}

/**
 * logoutUser - destruye sesión
 */
function logoutUser()
{
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"], $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    redirect('views/index.php?logout=success');
}