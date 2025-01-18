<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

// Verificar sesión
if (session_status() == PHP_SESSION_NONE) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php?error=Debe iniciar sesión para ver su perfil");
        exit();
    }
}

$user_id = $_SESSION['user_id'];

// Conexión a la base de datos
$conex = getDBConnection();

// 1) Obtener username, si no lo guardas en $_SESSION['username']
$sql_user = "SELECT username FROM usuarios WHERE id = ?";
$stmt_user = $conex->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$userData = $result_user->fetch_assoc();
$stmt_user->close();

$username = $userData['username'] ?? 'TuPerfil';

// 2) Contar total de eventos creados
$sql_count_creados = "SELECT COUNT(*) AS total_eventos_creados FROM eventos WHERE creador_id = ?";
$stmt_count_creados = $conex->prepare($sql_count_creados);
$stmt_count_creados->bind_param("i", $user_id);
$stmt_count_creados->execute();
$result_count_creados = $stmt_count_creados->get_result();
$row_count_creados = $result_count_creados->fetch_assoc();
$total_eventos_creados = $row_count_creados['total_eventos_creados'] ?? 0;
$stmt_count_creados->close();

// 3) (Opcional) Contar total de participaciones (eventos donde user_id participa, no solo crea)
$sql_count_participa = "SELECT COUNT(*) AS total_participaciones
                        FROM participaciones p
                        JOIN eventos e ON p.evento_id = e.id
                        WHERE p.usuario_id = ?";
$stmt_count_participa = $conex->prepare($sql_count_participa);
$stmt_count_participa->bind_param("i", $user_id);
$stmt_count_participa->execute();
$result_count_participa = $stmt_count_participa->get_result();
$row_count_participa = $result_count_participa->fetch_assoc();
$total_participaciones = $row_count_participa['total_participaciones'] ?? 0;
$stmt_count_participa->close();

// 4) Obtener lista de eventos creados
$sql_eventos = "SELECT e.*, u.username AS creador_username
                FROM eventos e
                JOIN usuarios u ON e.creador_id = u.id
                WHERE u.id = ?
                ORDER BY e.created_at DESC";
$stmt = $conex->prepare($sql_eventos);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$eventos_result = $stmt->get_result();
?>

<!-- Envolver todo en un container central -->
<div class="container mt-4" style="max-width: 800px;">

    <!-- Tarjeta con info del perfil -->
    <div class="profile-card mb-4 p-3">
        <h2 class="profile-card-title" style="color: var(--primary-color); text-align: center;">
            Perfil de <?php echo htmlspecialchars($username); ?>
        </h2>
        <div class="card-body">
            <p class="profile-card-text" style="text-align: center;">
                Has creado <strong><?php echo $total_eventos_creados; ?></strong> eventos.<br>
                Has participado en <strong><?php echo $total_participaciones; ?></strong> eventos.
            </p>
        </div>
    </div>

    <!-- Encabezado de tus eventos creados -->
    <h3 class="mb-4 text-center" style="color: var(--primary-color);">
        Tus Eventos Creados
    </h3>

    <!-- Lista de eventos creados -->
    <div id="eventos">
        <?php if ($eventos_result->num_rows > 0): ?>
            <?php while ($row = $eventos_result->fetch_assoc()): ?>
                <?php include 'includes/event_card.php'; ?>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No has creado ningún evento.</p>
        <?php endif; ?>
    </div>

</div>

<?php
$stmt->close();
$conex->close();
include('includes/footer.php');
?>
