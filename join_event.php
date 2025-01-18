<?php
require_once __DIR__ . '/includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verifico si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=Debe iniciar sesión para unirse a un evento");
    exit();
}

if (isset($_GET['id'])) {
    $event_id = sanitizeInput($_GET['id']);
    $user_id = $_SESSION['user_id'];

    $conex = getDBConnection();

    // Verifico si el usuario ya está registrado en el evento
    $sql_check = "SELECT * FROM participaciones WHERE evento_id = ? AND usuario_id = ?";
    $stmt_check = $conex->prepare($sql_check);
    $stmt_check->bind_param("ii", $event_id, $user_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Ya estás inscrito en este evento']);
    } else {
        // Inserto la participación del usuario en el evento
        $sql = "INSERT INTO participaciones (evento_id, usuario_id) VALUES (?, ?)";
        $stmt = $conex->prepare($sql);
        $stmt->bind_param("ii", $event_id, $user_id);

        if ($stmt->execute()) {
            // Obtener el nombre de usuario
            $username = $_SESSION['username'];

            // Obtener el número actualizado de participantes
            $sql_count = "SELECT COUNT(*) AS participant_count FROM participaciones WHERE evento_id = ?";
            $stmt_count = $conex->prepare($sql_count);
            $stmt_count->bind_param("i", $event_id);
            $stmt_count->execute();
            $stmt_count->bind_result($new_count);
            $stmt_count->fetch();
            $stmt_count->close();

            // Agregar una notificación para el creador del evento
            $creator_id_query = "SELECT creador_id FROM eventos WHERE id = ?";
            $stmt_creator = $conex->prepare($creator_id_query);
            $stmt_creator->bind_param("i", $event_id);
            $stmt_creator->execute();
            $stmt_creator->bind_result($creator_id);
            $stmt_creator->fetch();
            $stmt_creator->close();

            if ($creator_id && $creator_id != $user_id) {
                $message = "Un usuario se ha unido a tu evento.";
                $sql_notification = "INSERT INTO notifications (user_id, event_id, message) VALUES (?, ?, ?)";
                $stmt_notification = $conex->prepare($sql_notification);
                $stmt_notification->bind_param("iis", $creator_id, $event_id, $message);
                $stmt_notification->execute();
                $stmt_notification->close();
            }

            // Respuesta con el número actualizado de participantes y el nombre del usuario
            echo json_encode([
                'status' => 'success',
                'message' => 'Te has unido al evento',
                'new_count' => $new_count,
                'username' => $username,
                'user_id' => $user_id
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al unirse al evento']);
        }

        $stmt->close();
    }

    $stmt_check->close();
    $conex->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'No se ha especificado un evento']);
}
?>
