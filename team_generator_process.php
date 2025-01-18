<?php
// team_generator_process.php

require_once __DIR__ . '/includes/functions.php';

// Solo procesar solicitudes POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validar token CSRF
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrf_token)) {
        echo "<p style='color:red;'>Error: Token CSRF inválido.</p>";
        exit();
    }

    // Sanitizar entradas
    $titulo_evento = sanitizeInput($_POST['titulo_evento'] ?? '');
    $participantes_raw = $_POST['participantes'] ?? '';
    $division_method = sanitizeInput($_POST['division_method'] ?? 'teams'); // 'teams' o 'participants'
    $num_teams = (int)sanitizeInput($_POST['num_teams'] ?? 2); // Default a 2 equipos

    // Convertir participantes en un array, eliminando líneas vacías
    $participantes = array_filter(array_map('trim', preg_split("/\r\n|\r|\n/", $participantes_raw)));

    // Validación básica
    if (count($participantes) < 2) {
        echo "<p style='color:red;'>Error: Debes ingresar al menos 2 participantes.</p>";
    } elseif ($num_teams < 2) {
        echo "<p style='color:red;'>Error: La cantidad de equipos debe ser al menos 2.</p>";
    } else {
        // Mezclar participantes
        shuffle($participantes);

        // Calcular la cantidad de participantes por equipo
        if ($division_method === 'teams') {
            $participants_per_team = ceil(count($participantes) / $num_teams);
        } else {
            // Si se divide por participantes, recalcular número de equipos
            $participants_per_team = max(1, $num_teams);
            $num_teams = ceil(count($participantes) / $participants_per_team);
        }

        $teams = array_chunk($participantes, $participants_per_team);

        // Generar HTML de resultados
        ob_start(); // Iniciar buffer de salida

        foreach ($teams as $index => $team) {
                echo "<div class='team-card'>";
                    echo "<h5>Equipo " . ($index + 1) . "</h5>";
                    echo "<ol>";
                    foreach ($team as $member) {
                        echo "<li>" . htmlspecialchars($member) . "</li>";
                    }
                    echo "</ol>";
                echo "</div>";
        }

        // Mostrar la salida
        echo ob_get_clean();
    }
} else {
    // Si no es POST, mostrar un mensaje de error
    echo "<p style='color:red;'>Método de solicitud no permitido.</p>";
}
?>
