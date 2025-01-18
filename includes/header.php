<?php
// includes/header.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movolize</title>

    <!-- Estilos CSS personalizados -->
    <link rel="stylesheet" href="assets/css/movolize_styles.css">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Day.js (para manejar fechas y horas) -->
    <script src="https://cdn.jsdelivr.net/npm/dayjs/dayjs.min.js"></script>

    <!-- Axios (para manejar peticiones HTTP) -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <!-- Google Maps API con Places -->
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBRYp-kArD_qt-7FWKZFDOaVumGVjMAY8U&libraries=places"></script>

    <!-- Leaflet (opcional)
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css"
        integrity="sha256-RtII4y+xpy6REkeTts14EyN4vQtmT6bkebhN5y0B+B8=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"
        integrity="sha256-VRZdd9UxQauFSPF+xyynp7BxP1moYJEixle6sx6jDgQ="
        crossorigin=""></script>
    -->
</head>
<body>
<?php
// Incluir funciones y establecer la conexión
require_once __DIR__ . '/functions.php';
$conex = getDBConnection();

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Obtener el ID de usuario de la sesión (si existe)
$user_id = $_SESSION['user_id'] ?? null;

// Contar notificaciones no leídas
$unread_count = 0;
if ($user_id) {
    $sql_notifications = "SELECT COUNT(*) AS unread_count
                          FROM notifications
                          WHERE user_id = ?
                            AND is_read = 0";
    $stmt_notifications = $conex->prepare($sql_notifications);
    if ($stmt_notifications) {
        $stmt_notifications->bind_param("i", $user_id);
        $stmt_notifications->execute();
        $stmt_notifications->bind_result($unread_count);
        $stmt_notifications->fetch();
        $stmt_notifications->close();
    }
}

// Identificar el archivo actual para condicionar la barra de búsqueda
$current_file = basename($_SERVER['PHP_SELF']);

// ---- MOSTRAR ALERTAS SOLO UNA VEZ ----
if (isset($_GET['login']) && $_GET['login'] === 'success') {
    echo "<script>
        Swal.fire({
            icon: 'success',
            title: 'Has iniciado sesión',
            text: '¡Bienvenido a Movolize!',
            showConfirmButton: false,
            timer: 1500
        }).then(() => {
            window.history.replaceState({}, document.title, 'index.php');
        });
    </script>";
}

if (isset($_GET['register']) && $_GET['register'] === 'success') {
    echo "<script>
        Swal.fire({
            icon: 'success',
            title: 'Registro Exitoso',
            text: '¡Te has registrado en Movolize!',
            showConfirmButton: false,
            timer: 1500
        }).then(() => {
            window.history.replaceState({}, document.title, 'index.php');
        });
    </script>";
}
// ---------------------------------------
?>

<!-- Header principal -->
<header>
    <div class="header-container">
        <h1><a href="index.php">Movolize</a></h1>

        <!-- Filtros de búsqueda (solo en index.php, profile.php, view_events.php) -->
        <?php if (in_array($current_file, ['index.php','profile.php','view_events.php'])): ?>
            <div class="search-container">
                <div class="form-row">
                    <div class="col-md-4">
                        <select id="filter-deporte" name="deporte" class="form-control">
                            <option value="">Sin filtro</option>
                            <option value="Fútbol">Fútbol</option>
                            <option value="Pádel">Pádel</option>
                            <option value="Tenis">Tenis</option>
                            <option value="Baloncesto">Baloncesto</option>
                            <option value="Voleibol">Voleibol</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" id="filter-ciudad" name="ciudad" class="form-control" placeholder="Tu ciudad">
                    </div>
                    <div class="col-md-4">
                        <button type="button" onclick="applyFilters()" class="btn btn-primary">
                            <img src="assets/images/search.svg" alt="Buscar" class="svg-icon">
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Menú principal -->
        <nav class="main-menu">
            <ul>
                <li><a href="index.php">Inicio</a></li>
                <li><a href="create_event.php">Crear Evento</a></li>
                <li><a href="team_generator.php">Generar Equipos</a></li>
                <li>
                    <button id="theme-toggle">
                        <img src="assets/images/sun.svg" alt="Cambiar tema" id="theme-icon" class="svg-icon">
                    </button>
                </li>
                <li>
                    <!-- BOTÓN que controla el menú (usamos aria-* para accesibilidad) -->
                    <button 
                        class="profile-icon user-menu-toggle"
                        aria-haspopup="true"
                        aria-expanded="false"
                        aria-controls="user-menu"
                    >
                        <img src="assets/images/profile.svg" alt="Perfil" class="svg-icon">
                        <?php if ($unread_count > 0): ?>
                            <span class="notification-dot"></span>
                        <?php endif; ?>
                    </button>
                    <!-- Menú desplegable de perfil -->
                    <ul 
                        class="user-menu hidden"
                        id="user-menu"
                        role="menu"
                        aria-hidden="true"
                    >
                        <?php
                        if (isset($_SESSION['user_id'])) {
                            echo '<li role="menuitem"><a href="profile.php">Perfil</a></li>';
                            echo '<li role="menuitem"><a href="view_events.php">Mis Eventos</a></li>';
                            echo '<li role="menuitem"><a href="notifications.php">Notificaciones';
                            if ($unread_count > 0) {
                                echo ' <span class="notification-badge">' . htmlspecialchars($unread_count) . '</span>';
                            }
                            echo '</a></li>';
                            echo '<li role="menuitem"><a href="logout.php">Cerrar Sesión</a></li>';
                        } else {
                            echo '<li role="menuitem"><a href="login.php">Iniciar Sesión</a></li>';
                            echo '<li role="menuitem"><a href="register.php">Registrarse</a></li>';
                        }
                        ?>
                    </ul>
                </li>
            </ul>
        </nav>
    </div>
</header>

<main>

<!-- SCRIPT para controlar el menú por click, mejorar accesibilidad -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.querySelector('.user-menu-toggle');
    const menu = document.querySelector('.user-menu');

    document.addEventListener('click', function(e) {
        // Si se hace click en el botón toggle
        if (toggleBtn.contains(e.target)) {
            // Alternar la clase hidden
            menu.classList.toggle('hidden');

            // Actualizar atributos ARIA
            const isExpanded = toggleBtn.getAttribute('aria-expanded') === 'true';
            toggleBtn.setAttribute('aria-expanded', !isExpanded);
            menu.setAttribute('aria-hidden', isExpanded);

        } else if (!menu.contains(e.target)) {
            // Clic fuera del menú: cerrarlo
            menu.classList.add('hidden');
            toggleBtn.setAttribute('aria-expanded', false);
            menu.setAttribute('aria-hidden', true);
        }
    });
});
</script>
