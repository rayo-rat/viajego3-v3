<?php
// generar_pass.php
// Cambia 'TuContrasenaSuperSegura2025!' por la contraseña que quieras usar
$password = 'TuContrasenaSuperSegura2025!'; 
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Tu hash seguro es: <br><br>" . $hash;
?>