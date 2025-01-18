<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=Debe iniciar sesión para ver tus eventos");
    exit();
}

$user_id = $_SESSION['user_id'];

// Conexión a la base de datos
$conex = getDBConnection();

// Obtener los eventos en los que el usuario es participante o creador
$sql = "SELECT e.*, u.username AS creador FROM eventos e
        JOIN usuarios u ON e.creador_id = u.id
        LEFT JOIN participaciones p ON e.id = p.evento_id
        WHERE e.creador_id = ? OR p.usuario_id = ?
        GROUP BY e.id
        ORDER BY e.fecha_hora DESC";
$stmt = $conex->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$eventos_result = $stmt->get_result();

?>
<div class="container mt-4">

    <div id="eventos">
        <?php if ($eventos_result->num_rows > 0): ?>
            <?php while ($row = $eventos_result->fetch_assoc()): ?>
                <?php include 'includes/event_card.php'; ?>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No estás participando en ningún evento.</p>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
