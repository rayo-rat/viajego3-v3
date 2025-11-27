<?php
// controllers/login.php
require_once __DIR__ . '/../models/includes/config.php';
require_once __DIR__ . '/../models/includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $res = loginUser($email, $password);

    if ($res['success']) {
        // ✅ CORRECTO: NO usar BASE_URL dentro del redirect()
        redirect('views/index.php');
    } else {
        $error = $res['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - ViajeGO</title>

    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <h2>Iniciar Sesión</h2>

        <?php if ($error): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Correo:</label>
                <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" name="password" required>
            </div>

            <button class="btn-primary" type="submit">Entrar</button>
        </form>

        <p>¿No tienes cuenta? <a href="<?= BASE_URL ?>controllers/register.php">Regístrate</a></p>
        
        <p><a href="<?= BASE_URL ?>views/index.php">Volver al inicio</a></p>
    </div>
</body>
</html>
