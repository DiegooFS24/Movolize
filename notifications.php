<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conex = getDBConnection();
$user_id = $_SESSION['user_id'];

// Obtener las notificaciones
$sql_notifications = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$stmt_notifications = $conex->prepare($sql_notifications);
$stmt_notifications->bind_param("i", $user_id);
$stmt_notifications->execute();
$result = $stmt_notifications->get_result();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Notificaciones</title>
    <link rel="stylesheet" href="assets/css/movolize_styles.css">
</head>
<body>
    <div class="notification-card">
        <h2>Tus notificaciones</h2>
        <ul class="notification-list">
            <?php while ($notification = $result->fetch_assoc()): ?>
                <li class="<?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                    <p><?php echo htmlspecialchars($notification['message']); ?></p>
                    <span><?php echo $notification['created_at']; ?></span>
                    <?php if (!$notification['is_read']): ?>
                        <strong>(No leída)</strong>
                    <?php endif; ?>
                </li>
            <?php endwhile; ?>
        </ul>
    </div>
    <?php
    // Marcar todas las notificaciones como leídas
    $mark_as_read_query = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
    $stmt_mark_as_read = $conex->prepare($mark_as_read_query);
    $stmt_mark_as_read->bind_param("i", $user_id);
    $stmt_mark_as_read->execute();
    ?>
</body>
</html>
