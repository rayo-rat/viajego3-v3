<?php
// controllers/register.php
require_once __DIR__ . '/../models/includes/config.php';
require_once __DIR__ . '/../models/includes/auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'username' => trim($_POST['username'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
    ];

    $res = registerUser($data);

    if ($res['success']) {
        $success = $res['message'];
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
    <title>Registro - ViajeGO</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <h2>Crear Cuenta</h2>

        <?php if ($success): ?>
            <div class="alert success"><?= e($success) ?></div>
            <p><a href="<?= BASE_URL ?>controllers/login.php">Ir a iniciar sesión</a></p>

        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Nombre de usuario:</label>
                    <input type="text" name="username" required value="<?= e($_POST['username'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="email">Correo:</label>
                    <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="password">Contraseña:</label>
                    <input type="password" name="password" required>
                </div>

                <button class="btn-primary" type="submit">Registrar</button>
            </form>

            <p>¿Ya tienes cuenta? 
                <a href="<?= BASE_URL ?>controllers/login.php">Inicia sesión</a>
            </p>
        <?php endif; ?>

        <p><a href="<?= BASE_URL ?>views/index.php">Volver al inicio</a></p>
    </div>
</body>
</html>
