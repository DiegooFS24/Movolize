<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Obtener el ID del usuario desde la URL
if (!isset($_GET['id'])) {
    echo "<p>Error: No se ha especificado un ID de usuario</p>";
    exit();
}

$user_id = sanitizeInput($_GET['id']);

// Conectar a la base de datos
$conex = getDBConnection();

// Obtener los datos del usuario
$sql = "SELECT username, email FROM usuarios WHERE id = ?";
$stmt = $conex->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "<p>Error: Usuario no encontrado.</p>";
    exit();
}

?>
<div class="container mt-4">
    <h2 class="mb-4">Perfil de <?php echo htmlspecialchars($user['username']); ?></h2>
    <div class="profile_view_text">
        <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
        <p>Eventos creados: </p>
    <h3 class="mt-4">Eventos creados por <?php echo htmlspecialchars($user['username']); ?></h3>
    <br>
    </div>

    <?php
    // Obtener los eventos creados por el usuario
    $sql_events = "SELECT e.*, u.username AS creador_username FROM eventos e JOIN usuarios u ON e.creador_id = u.id WHERE u.id = ? ORDER BY e.created_at DESC";
    $stmt_events = $conex->prepare($sql_events);
    $stmt_events->bind_param("i", $user_id);
    $stmt_events->execute();
    $eventos_result = $stmt_events->get_result();
    $stmt_events->close();

    if ($eventos_result->num_rows > 0): ?>
        <div id="eventos">
            <?php while ($row = $eventos_result->fetch_assoc()): ?>
                <?php include 'includes/event_card.php'; ?>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p>No ha creado ningún evento.</p>
    <?php endif; ?>
</div>

<?php
include('includes/footer.php');
?>
