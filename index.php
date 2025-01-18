<?php
// index.php

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$conex = getDBConnection();

// Obtener los filtros de deporte y ciudad si existen
$deporte = isset($_GET['deporte']) ? '%' . sanitizeInput($_GET['deporte']) . '%' : '%';
$ciudad = isset($_GET['ciudad']) ? '%' . sanitizeInput($_GET['ciudad']) . '%' : '%';

// Manejar la paginación
$eventosPorPagina = 10;
$paginaActual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$paginaActual = max($paginaActual, 1); // Asegura que la página sea al menos 1
$offset = ($paginaActual - 1) * $eventosPorPagina;

// Contar el total de eventos filtrados
$sql_total = "SELECT COUNT(*) AS total FROM eventos WHERE deporte LIKE ? AND ubicacion LIKE ?";
$stmt_total = $conex->prepare($sql_total);
$stmt_total->bind_param("ss", $deporte, $ciudad);
$stmt_total->execute();
$result_total = $stmt_total->get_result();
$totalEventos = $result_total->fetch_assoc()['total'];
$totalPaginas = ceil($totalEventos / $eventosPorPagina);
$stmt_total->close();

// Obtener los eventos filtrados según la página actual
$sql = "SELECT e.*, u.username AS creador_username FROM eventos e
        JOIN usuarios u ON e.creador_id = u.id
        WHERE e.deporte LIKE ? AND e.ubicacion LIKE ?
        ORDER BY e.created_at DESC
        LIMIT ? OFFSET ?";
$stmt = $conex->prepare($sql);
$stmt->bind_param("ssii", $deporte, $ciudad, $eventosPorPagina, $offset);
$stmt->execute();
$eventos_result = $stmt->get_result();
?>

<!-- Mostrar eventos filtrados -->
<div id="eventos">
    <?php if ($eventos_result->num_rows > 0): ?>
        <?php while ($row = $eventos_result->fetch_assoc()): ?>
            <?php include 'includes/event_card.php'; ?>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No hay eventos disponibles.</p>
    <?php endif; ?>
</div>

<!-- Paginación -->
<div class="pagination">
    <?php if ($paginaActual > 1): ?>
        <a href="index.php?page=<?php echo $paginaActual - 1; ?>&deporte=<?php echo urlencode($_GET['deporte'] ?? ''); ?>&ciudad=<?php echo urlencode($_GET['ciudad'] ?? ''); ?>">Anterior</a>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
        <a href="index.php?page=<?php echo $i; ?>&deporte=<?php echo urlencode($_GET['deporte'] ?? ''); ?>&ciudad=<?php echo urlencode($_GET['ciudad'] ?? ''); ?>" <?php if ($i == $paginaActual) echo 'class="active"'; ?>>
            <?php echo $i; ?>
        </a>
    <?php endfor; ?>

    <?php if ($paginaActual < $totalPaginas): ?>
        <a href="index.php?page=<?php echo $paginaActual + 1; ?>&deporte=<?php echo urlencode($_GET['deporte'] ?? ''); ?>&ciudad=<?php echo urlencode($_GET['ciudad'] ?? ''); ?>">Siguiente</a>
    <?php endif; ?>
</div>

<?php
include('includes/footer.php');
?>
