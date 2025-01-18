<?php
// includes/event_card.php
// Este archivo espera que las variables $row, $conex y $user_id estén definidas

// Obtener los participantes del evento
$sql_participants = "SELECT u.id, u.username FROM participaciones p JOIN usuarios u ON p.usuario_id = u.id WHERE p.evento_id = ?";
$stmt_participants = $conex->prepare($sql_participants);
if (!$stmt_participants) {
    echo "<p>Error al preparar la consulta de participantes: " . $conex->error . "</p>";
    exit();
}
$stmt_participants->bind_param("i", $row['id']);
$stmt_participants->execute();
$result_participants = $stmt_participants->get_result();
$participants = [];
while ($participant = $result_participants->fetch_assoc()) {
    $participants[] = $participant;
}
$stmt_participants->close();

// Verificar si el creador ya está en la lista de participantes
$creator_included = false;
foreach ($participants as $participant) {
    if ($participant['id'] == $row['creador_id']) {
        $creator_included = true;
        break;
    }
}

if (!$creator_included) {
    $participants[] = [
        'id' => $row['creador_id'],
        'username' => $row['creador_username']
    ];
}

$participants_count = count($participants);
?>

<div class="event-card card mb-4" id="event-<?php echo htmlspecialchars($row['id']); ?>">
    <div class="card-body">
        <h3 class="card-title"><?php echo htmlspecialchars($row['deporte']); ?></h3>
        <p class="card-text">Ubicación:
            <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($row['ubicacion']); ?>" target="_blank">
                <?php echo htmlspecialchars($row['ubicacion']); ?>
            </a>
        </p>
        <p class="card-text">Fecha: <?php echo date('d - m - Y', strtotime($row['fecha_hora'])); ?></p>
        <p class="card-text">Hora: <?php echo date('H:i', strtotime($row['fecha_hora'])); ?></p>

        <?php if (!empty($row['descripcion'])): ?>
            <p class="card-text">Descripción: <?php echo htmlspecialchars($row['descripcion']); ?></p>
        <?php endif; ?>

        <!-- Sección de Participantes -->
        <div class="participants mt-3">
            <p>
                <img src="assets/images/profile.svg" alt="Participantes" class="participantes theme-dependent-icon">
                <?php echo $participants_count; ?>
            </p>
            <div class="participant-list" style="display: none;">
                <ul>
                    <li>
                        <a href="profile_view.php?id=<?php echo $row['creador_id']; ?>">
                            <?php echo htmlspecialchars($row['creador_username']); ?>
                        </a>
                        <img src="assets/images/owner.svg" alt="Owner" class="svg-icon">
                    </li>
                    <?php foreach ($participants as $participant): ?>
                        <?php if ($participant['id'] != $row['creador_id']): ?>
                            <li>
                                <a href="profile_view.php?id=<?php echo $participant['id']; ?>">
                                    <?php echo htmlspecialchars($participant['username']); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Botón de Unirse al Evento -->
        <?php if (isset($user_id)): ?>
            <?php
            $is_creator = $row['creador_id'] == $user_id;

            // Verificar si el usuario ya es participante
            $sql_participant_check = "SELECT * FROM participaciones WHERE evento_id = ? AND usuario_id = ?";
            $stmt_participant = $conex->prepare($sql_participant_check);
            if (!$stmt_participant) {
                echo "<p>Error al preparar la consulta de participación: " . $conex->error . "</p>";
                exit();
            }
            $stmt_participant->bind_param("ii", $row['id'], $user_id);
            $stmt_participant->execute();
            $result_participant = $stmt_participant->get_result();
            $is_participant = $result_participant->num_rows > 0;
            $stmt_participant->close();
            ?>

            <?php if (!$is_creator && !$is_participant): ?>
                <button class="join-event btn btn-success" onclick="joinEvent(<?php echo $row['id']; ?>)">Unirse al Evento</button>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Botón de Eliminar (Solo para el creador del evento) -->
        <?php if (isset($user_id) && $row['creador_id'] == $user_id): ?>
            <button class="delete-event btn btn-danger mt-2" onclick="confirmDeletion(<?php echo $row['id']; ?>)">
                <img src="assets/images/trash-can.svg" alt="Eliminar" class="svg-icon">
            </button>
        <?php endif; ?>
    </div>
</div>

<script>
// Mostrar la lista de participantes al pasar el ratón por encima
document.querySelectorAll('.participants').forEach(function(element) {
    element.addEventListener('mouseover', function() {
        this.querySelector('.participant-list').style.display = 'block';
    });
    element.addEventListener('mouseout', function() {
        this.querySelector('.participant-list').style.display = 'none';
    });
});

// Función para unirse al evento
function joinEvent(eventId) {
    console.log('Unirse al evento ID:', eventId);
    fetch('join_event.php?id=' + eventId) // Asegúrate de que la ruta sea correcta
        .then(response => response.json())
        .then(data => {
            console.log('Respuesta de join_event.php:', data);
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Te has unido al evento!',
                    showConfirmButton: false,
                    timer: 1500
                });
                // Actualizar la lista de participantes sin recargar la página
                const participantList = document.querySelector(`#event-${eventId} .participant-list ul`);
                const newParticipant = `<li><a href="profile_view.php?id=${data.user_id}">${data.username}</a></li>`;
                participantList.innerHTML += newParticipant;

                const participantCount = document.querySelector(`#event-${eventId} .participants p`);
                participantCount.innerHTML = `<img src="assets/images/profile.svg" alt="Participantes" class="participantes theme-dependent-icon"> ${data.new_count}`;  // Actualiza el número de participantes con la imagen
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error en fetch:', error);
            Swal.fire('Error', 'Ocurrió un problema al unirse al evento', 'error');
        });
}

// Función para confirmar la eliminación usando SweetAlert2
function confirmDeletion(eventId) {
    console.log('Confirmar eliminación para evento ID:', eventId);
    Swal.fire({
        title: '¿Estás seguro?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            console.log('Eliminación confirmada para evento ID:', eventId);
            // Llamar a la función que realiza la eliminación
            deleteEvent(eventId);
        }
    });
}

// Función para realizar la eliminación del evento
function deleteEvent(eventId) {
    console.log('Eliminando evento ID:', eventId);
    // Realizar una petición POST a delete_event.php
    fetch('delete_event.php', { // Asegúrate de que la ruta sea correcta
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({ event_id: eventId })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Respuesta de delete_event.php:', data);
        if (data.status === 'success') {
            // Eliminar la tarjeta del evento del DOM
            const eventCard = document.getElementById('event-' + eventId);
            if (eventCard) {
                eventCard.remove();
            }

            // Mostrar una notificación de éxito
            Swal.fire({
                icon: 'success',
                title: 'Evento eliminado',
                text: data.message,
                showConfirmButton: false,
                timer: 1500
            });
        } else {
            // Mostrar una notificación de error
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error en fetch:', error);
        Swal.fire('Error', 'Ocurrió un problema al eliminar el evento.', 'error');
    });
}
</script>
