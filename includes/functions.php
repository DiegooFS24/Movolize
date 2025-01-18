<?php
// includes/functions.php

// Conexión a la base de datos
function getDBConnection() {
    $servername = "localhost"; // Confirmado que con localhost funcionna
    $username   = "u557300417_movolize";
    $password   = "QQ789/qq";
    $dbname     = "u557300417_movolize";

    $conex = new mysqli($servername, $username, $password, $dbname);
    if ($conex->connect_error) {
        die("Error de conexión a la base de datos: " . $conex->connect_error);
    }
    return $conex;
}

// Generar un token CSRF
function generateCSRFToken() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validar el token CSRF
function validateCSRFToken($token) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Sanitizar las entradas del usuario
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}
?>
