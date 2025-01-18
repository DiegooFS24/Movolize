<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

// Verificar si la sesión ya está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    echo "<div class='session_required'><p>Debe <a href='login.php'>iniciar sesión</a> o <a href='register.php'>registrarse</a> para crear un evento.</p></div>";
    include('includes\footer.php');
    exit();
}
?>

<form id="create-event-form" action="create_event.php" method="post">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>"> <!-- Token CSRF -->

    <div class="form-group">
        <label for="deporte">Deporte:</label>
        <select id="deporte" name="deporte" class="form-control" required>
            <option value="">Selecciona un deporte</option>
            <option value="Fútbol 5">Fútbol 5</option>
            <option value="Fútbol 7">Fútbol 7</option>
            <option value="Fútbol 11">Fútbol 11</option>
            <option value="Pádel">Pádel</option>
            <option value="Tenis">Tenis</option>
            <option value="Baloncesto">Baloncesto</option>
            <option value="Voleibol">Voleibol</option>
        </select>
    </div>

    <div class="form-group">
        <label for="ubicacion">Ubicación:</label>
        <input type="text" id="ubicacion" name="ubicacion" class="form-control" required placeholder="Introduce una ubicación o elige en el mapa">
    </div>

    <div id="map" style="height: 300px;"></div> <!-- Muestra el mapa aquí -->

    <div class="form-group mt-3">
        <label for="fecha_hora">Fecha y Hora:</label>
        <input type="datetime-local" id="fecha_hora" name="fecha_hora" class="form-control" required>
    </div>

    <div class="form-group">
        <label for="descripcion">Descripción:</label>
        <textarea id="descripcion" name="descripcion" class="form-control"></textarea>
    </div>

    <button type="submit" class="btn btn-primary" id="create-event">Crear Evento</button>
</form>

<script>
function initAutocomplete() {
    const input = document.getElementById('ubicacion');
    let selectedPlace = null;
    let map;
    let marker;

    const autocomplete = new google.maps.places.Autocomplete(input, {
        types: ['geocode', 'establishment'], // Permitir lugares y direcciones
        fields: ['geometry', 'formatted_address', 'name'] // Pedimos nombre y dirección
    });

    autocomplete.addListener('place_changed', function() {
        selectedPlace = autocomplete.getPlace();

        if (!selectedPlace.geometry) {
            alert("Por favor, selecciona una ubicación válida.");
            return;
        }

        // Usamos el nombre del lugar o la dirección
        if (selectedPlace.formatted_address) {
            input.value = selectedPlace.formatted_address; // Usamos la dirección si está disponible
        } else if (selectedPlace.name) {
            input.value = selectedPlace.name; // Usamos el nombre del lugar si está disponible
        }

        // Inicializamos el mapa si aún no lo está
        if (!map) {
            map = new google.maps.Map(document.getElementById('map'), {
                center: selectedPlace.geometry.location,
                zoom: 15
            });
        }

        // Movemos el mapa a la nueva ubicación seleccionada
        map.setCenter(selectedPlace.geometry.location);

        // Colocamos el marcador en la ubicación seleccionada
        if (!marker) {
            marker = new google.maps.Marker({
                map: map,
                position: selectedPlace.geometry.location,
                draggable: true // Permitimos mover el marcador
            });
        } else {
            marker.setPosition(selectedPlace.geometry.location);
        }
    });

    // Permitir seleccionar la ubicación haciendo clic en el mapa
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const userLocation = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
            };

            map = new google.maps.Map(document.getElementById('map'), {
                center: userLocation,
                zoom: 12
            });

            // Añadir un clic en el mapa para seleccionar una ubicación
            map.addListener('click', function(event) {
                const clickedLocation = event.latLng;

                if (!marker) {
                    marker = new google.maps.Marker({
                        map: map,
                        position: clickedLocation,
                        draggable: true // Permitimos mover el marcador
                    });
                } else {
                    marker.setPosition(clickedLocation);
                }

                // Geocodificar la ubicación seleccionada para obtener un nombre
                const geocoder = new google.maps.Geocoder();
                geocoder.geocode({ location: clickedLocation }, function(results, status) {
                    if (status === 'OK' && results[0]) {
                        input.value = results[0].formatted_address; // Mostramos la dirección en el campo de texto
                    } else {
                        input.value = clickedLocation.lat() + ", " + clickedLocation.lng();
                    }
                });
            });
        });
    }

    // Validar el formulario para asegurarnos de que se selecciona una ubicación válida
    document.getElementById('create-event-form').addEventListener('submit', function(event) {
        if (!selectedPlace || (!selectedPlace.formatted_address && !selectedPlace.name)) {
            alert("Debes seleccionar una ubicación válida utilizando el Autocomplete.");
            event.preventDefault();
        }
    });
}

google.maps.event.addDomListener(window, 'load', initAutocomplete);
</script>

<?php
// Lógica para insertar evento y añadir al creador como participante
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        die("<p>Error: Token CSRF no válido</p>");
    }

    $deporte = sanitizeInput($_POST['deporte']);
    $ubicacion = sanitizeInput($_POST['ubicacion']);
    $fecha_hora = sanitizeInput($_POST['fecha_hora']);
    $descripcion = sanitizeInput($_POST['descripcion']);
    $creador_id = $_SESSION['user_id'];

    $conex = getDBConnection();

    // Inserción del evento en la tabla 'eventos'
    $sql = "INSERT INTO eventos (deporte, ubicacion, fecha_hora, creador_id, descripcion) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conex->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sssis", $deporte, $ubicacion, $fecha_hora, $creador_id, $descripcion);

        if ($stmt->execute()) {
            // Obtener el ID del evento creado
            $event_id = $stmt->insert_id;

            // Verificar si el creador ya está añadido como participante
            $sql_check_participant = "SELECT * FROM participaciones WHERE evento_id = ? AND usuario_id = ?";
            $stmt_check = $conex->prepare($sql_check_participant);
            $stmt_check->bind_param("ii", $event_id, $creador_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            // Solo añadir al creador si no está ya en la lista de participantes
            if ($result_check->num_rows == 0) {
                $sql_participation = "INSERT INTO participaciones (evento_id, usuario_id) VALUES (?, ?)";
                $stmt_participation = $conex->prepare($sql_participation);
                $stmt_participation->bind_param("ii", $event_id, $creador_id);
                $stmt_participation->execute();
                $stmt_participation->close();
            }

            $stmt_check->close();

            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Evento creado exitosamente',
                    showConfirmButton: false,
                    timer: 1500
                });
            </script>";
        } else {
            echo "<p>Error: " . $stmt->error . "</p>";
        }

        $stmt->close();
    } else {
        echo "<p>Error en la preparación de la declaración: " . $conex->error . "</p>";
    }

    $conex->close();
}
?>

<br>

<?php
include('includes/footer.php');
?>
