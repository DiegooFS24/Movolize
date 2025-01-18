<?php
// db_conex.php
function getDBConnection() {
    $servername = "localhost"; // Confirmado que funciona
    $username   = "u557300417_movolize";
    $password = "QQ789/qq";
    $dbname     = "u557300417_movolize";

    $conex = new mysqli($servername, $username, $password, $dbname);
    if ($conex->connect_error) {
        die("Error de conexión: " . $conex->connect_error);
    }
    return $conex;
}
