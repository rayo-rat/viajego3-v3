<?php
// controllers/admin.php
require_once __DIR__ . '/../models/includes/config.php';
require_once __DIR__ . '/../models/includes/auth.php';

requireAdmin();

// Procesar acciones POST (aprobar/rechazar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['reservation_id'])) {
        $reservation_id = intval($_POST['reservation_id']);
        $admin_note = trim($_POST['admin_note'] ?? '');

        if (isset($_POST['approve'])) {
            $action = 'approved';
        } elseif (isset($_POST['reject'])) {
            $action = 'rejected';
        } else {
            $action = 'pending';
        }

        $stmt = $pdo->prepare("
            UPDATE reservations 
            SET status = :status, admin_note = :note 
            WHERE id = :id
        ");

        if ($stmt->execute([
            'status' => $action,
            'note' => $admin_note,
            'id' => $reservation_id
        ])) {
            $_SESSION['admin_message'] = "Reserva #$reservation_id " .
                ($action === 'approved' ? 'aprobada' : 'rechazada') . " correctamente.";
        } else {
            $_SESSION['admin_message'] = "Error al actualizar la reserva #$reservation_id";
        }

        // ✅ CORREGIDO - NO debe llevar BASE_URL aquí
        redirect('controllers/admin.php');
    }
}

// Obtener reservas pendientes
$stmt = $pdo->query("
    SELECT r.*, u.username, u.email, p.name as package_name, h.name as hotel_name 
    FROM reservations r 
    JOIN users u ON r.user_id = u.id 
    JOIN packages p ON r.package_id = p.id 
    JOIN hotels h ON r.hotel_id = h.id 
    WHERE r.status = 'pending'
    ORDER BY r.created_at DESC
");
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Panel de Administración - ViajeGO</title>

    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="container">
                <h1 class="logo">Viaje<span>GO</span> Admin</h1>
                <ul class="nav-links">
                    <li><a href="<?= BASE_URL ?>views/index.php">Volver al Sitio</a></li>
                    <li><a href="<?= BASE_URL ?>views/admin_crud.php">CRUD Servicios</a></li>
                    <li><a href="<?= BASE_URL ?>controllers/logout.php">Cerrar Sesión</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main>
        <div class="container">
            <h2>Reservas Pendientes</h2>

            <?php if (isset($_SESSION['admin_message'])): ?>
                <div class="alert <?= strpos($_SESSION['admin_message'], 'Error') !== false ? 'error' : 'success' ?>">
                    <?= e($_SESSION['admin_message']); unset($_SESSION['admin_message']); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($reservations)): ?>
                <div class="alert info">No hay reservas pendientes.</div>
            <?php else: ?>
                <div class="reservations-list">
                    <?php foreach ($reservations as $r): ?>
                        <div class="reservation-card admin">
                            <div class="reservation-info">
                                <h3>Reserva #<?= e($r['id']) ?></h3>
                                <div><strong>Usuario:</strong> <?= e($r['username']) ?> (<?= e($r['email']) ?>)</div>
                                <p><strong>Paquete:</strong> <?= e($r['package_name']) ?></p>
                                <p><strong>Hotel:</strong> <?= e($r['hotel_name']) ?></p>
                                <p><strong>Fechas:</strong> <?= e($r['start_date']) ?> → <?= e($r['end_date']) ?></p>
                                <p><strong>Personas:</strong> <?= e($r['adults']) ?> adultos, <?= e($r['children']) ?> niños</p>
                                <p><strong>Precio Total:</strong> $<?= number_format($r['total_price'], 2) ?></p>
                                <p><strong>Creada:</strong> <?= e($r['created_at']) ?></p>
                            </div>

                            <div class="reservation-actions">
                                <form method="POST" class="action-form">
                                    <input type="hidden" name="reservation_id" value="<?= e($r['id']) ?>">
                                    
                                    <div class="form-group">
                                        <label for="admin_note_<?= e($r['id']) ?>">Nota para el usuario:</label>
                                        <textarea id="admin_note_<?= e($r['id']) ?>" name="admin_note" rows="3"><?= e($r['admin_note'] ?? '') ?></textarea>
                                    </div>

                                    <div class="action-buttons">
                                        <button type="submit" name="approve" class="btn-success">Aprobar</button>
                                        <button type="submit" name="reject" class="btn-danger">Rechazar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>