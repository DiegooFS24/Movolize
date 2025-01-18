<?php
// delete_event.php

// Desactivar la visualización de errores en producción
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Incluir funciones y conexión a la base de datos
require_once __DIR__ . '/includes/functions.php';

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Debe iniciar sesión para eliminar un evento']);
    exit();
}

if (isset($_POST['event_id'])) {
    $event_id = intval($_POST['event_id']);
    $user_id = $_SESSION['user_id'];

    // Obtener la conexión usando la función central
    $conex = getDBConnection();

    // Verificar que el evento existe y que el usuario es el creador
    $sql_verify = "SELECT id FROM eventos WHERE id = ? AND creador_id = ?";
    $stmt_verify = $conex->prepare($sql_verify);
    if (!$stmt_verify) {
        echo json_encode(['status' => 'error', 'message' => 'Error al preparar la consulta: ' . $conex->error]);
        exit();
    }
    $stmt_verify->bind_param("ii", $event_id, $user_id);
    $stmt_verify->execute();
    $stmt_verify->store_result();

    if ($stmt_verify->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Evento no encontrado o no tienes permisos para eliminarlo']);
        $stmt_verify->close();
        $conex->close();
        exit();
    }
    $stmt_verify->close();

    // Eliminar el evento
    $sql_delete_event = "DELETE FROM eventos WHERE id = ? AND creador_id = ?";
    $stmt_delete_event = $conex->prepare($sql_delete_event);
    if (!$stmt_delete_event) {
        echo json_encode(['status' => 'error', 'message' => 'Error al preparar la eliminación: ' . $conex->error]);
        exit();
    }
    $stmt_delete_event->bind_param("ii", $event_id, $user_id);

    if ($stmt_delete_event->execute()) {
        // Eliminar también las participaciones asociadas
        $sql_delete_participations = "DELETE FROM participaciones WHERE evento_id = ?";
        $stmt_delete_participations = $conex->prepare($sql_delete_participations);
        if ($stmt_delete_participations) {
            $stmt_delete_participations->bind_param("i", $event_id);
            $stmt_delete_participations->execute();
            $stmt_delete_participations->close();
        }

        // Devolver una respuesta exitosa en formato JSON
        echo json_encode(["status" => "success", "message" => "Evento eliminado exitosamente"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error al eliminar el evento: " . $stmt_delete_event->error]);
    }

    $stmt_delete_event->close();
    $conex->close();
} else {
    echo json_encode(["status" => "error", "message" => "ID de evento no proporcionado"]);
}
?>
