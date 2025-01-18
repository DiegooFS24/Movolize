<?php
// team_generator.php

// Habilitar la visualización de errores (Solo para desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir el header
require_once __DIR__ . '/includes/header.php';

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Generar un token CSRF
$csrf_token = generateCSRFToken();
?>

<!-- Formulario para generar equipos -->
<form id="team-form" action="team_generator_process.php" method="post">
    <!-- Campo oculto para el token CSRF -->
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

    <!-- 1. Ingresa los participantes -->
    <div class="row">
        <div class="col-12 col-md-6 col-lg-6">
            <div class="form-group mb-0">
                <label class="label-counter" for="participantes">1. Ingresa los participantes</label>
                <div class="_pr" style="position: relative;">
                    <textarea
                        spellcheck="false"
                        data-gramm="false"
                        data-gramm_editor="false"
                        data-enable-grammarly="false"
                        placeholder="Cada participante debe estar en una nueva linea"
                        cols="30"
                        rows="11"
                        class="form-control form-control-lg p-4"
                        id="participantes"
                        name="participantes"
                        required
                    ></textarea>
                    <span class="badge badge-brand" style="position: absolute; right: 12px; bottom: 16px;" id="participant-count">0</span>
                </div>
                <p class="mt-2 _fs14 mb-0 text-muted">
                    Agrega un * para indicar líderes de grupo. *Líder
                </p>
            </div>
        </div>

        <!-- 2. Cómo dividir -->
        <div class="col-12 col-md-6 col-lg-6">
            <div class="form-group mt-4 mt-md-0">
                <div>
                    <label class="mb-3">2. Cómo dividir:</label>
                </div>
                <div class="form-check form-check-inline">
                    <input type="radio" id="byTeams" name="division_method" value="teams" class="form-check-input" checked>
                    <label for="byTeams" class="form-check-label mb-0 _fw400 _fs15">Cantidad de equipos</label>
                </div>
                <div class="form-check form-check-inline">
                    <input type="radio" id="byPart" name="division_method" value="participants" class="form-check-input">
                    <label for="byPart" class="form-check-label mb-0 _fw400 _fs15">Participantes por equipo</label>
                </div>
                <select class="mt-2 form-control form-control-lg custom-select custom-select-lg" id="num_teams" name="num_teams" required>
                    <option value="2" selected>2 equipos</option>
                    <option value="3">3 equipos</option>
                    <option value="4">4 equipos</option>
                    <option value="5">5 equipos</option>
                    <option value="6">6 equipos</option>
                    <option value="7">7 equipos</option>
                    <option value="8">8 equipos</option>
                    <option value="9">9 equipos</option>
                </select>
            </div>

            <!-- 3. Título -->
            <div class="form-group">
                <label for="titulo_evento">3. Título</label>
                <input
                    type="text"
                    id="titulo_evento"
                    name="titulo_evento"
                    placeholder="Copa del Mundo Qatar 2022"
                    class="form-control form-control-lg p-4"
                >
            </div>

            <!-- Botones Limpiar y Generar Equipos -->
            <div class="d-flex _jce mt-5">
                <button type="reset" class="btn btn-link mr-3 _fw600 _fs16" style="flex: 1;">Limpiar</button>
                <button type="button" id="generate-teams-btn" class="btn btn-primary btn-lg _fw600" style="flex: 2;">
                    Generar equipos
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Spinner de carga eliminado -->

<!-- Resultados de Equipos -->
<div id="teams-result">
    <h4 class="w-100 px-3 my-2">
        <span>Sorteo de Equipos</span>
        <a href="#" class="_fs16 printhidden text-muted">(Editar Título)</a>
    </h4>
    <div class="row">
        <!-- Los equipos generados aparecerán aquí -->
    </div>
    <!-- Botón Único "Volver a Mezclar" -->
    <div class="text-center">
        <button class="remix-button btn btn-primary btn-lg _fw700 _fs16 mt-4">
            Generar nuevos equipos
        </button>
    </div>
</div>

<!-- Script JavaScript específico para la generación de equipos -->
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const teamForm = document.getElementById('team-form');
    const teamsResultDiv = document.getElementById('teams-result');
    const generateTeamsBtn = document.getElementById('generate-teams-btn');
    const remixButton = teamsResultDiv.querySelector('.remix-button');
    const participantesTextarea = document.getElementById('participantes');
    const participantCountBadge = document.getElementById('participant-count');
    // Spinner eliminado

    // Actualizar contador de participantes en tiempo real
    participantesTextarea.addEventListener('input', function() {
        const count = participantesTextarea.value
            .split(/\r\n|\r|\n/)
            .filter(line => line.trim() !== '').length;
        participantCountBadge.textContent = count;
    });

    // Definir generateTeams como una función global
    window.generateTeams = function() {
        // Obtener los datos del formulario
        const formData = new FormData(teamForm);

        // Deshabilitar el botón y mostrar el spinner (eliminar estas líneas)
        // generateTeamsBtn.disabled = true;
        // spinner.style.display = 'block';
        teamsResultDiv.classList.remove('visible'); // Ocultar resultados previos

        // Enviar la solicitud AJAX usando Axios
        axios.post('team_generator_process.php', formData)
            .then(function(response) {
                // Mostrar los equipos generados en el div
                const teamsRow = teamsResultDiv.querySelector('.row');
                if (teamsRow) {
                    teamsRow.innerHTML = response.data;
                } else {
                    console.error('El contenedor .row dentro de #teams-result no existe.');
                }
                teamsResultDiv.classList.add('visible'); // Mostrar resultados
            })
            .catch(function(error) {
                console.error('Error al generar equipos:', error);
                const teamsRow = teamsResultDiv.querySelector('.row');
                if (teamsRow) {
                    teamsRow.innerHTML = "<p style='color:red;'>Ocurrió un error al generar los equipos.</p>";
                }
                teamsResultDiv.classList.add('visible'); // Mostrar mensaje de error
            })
            .finally(function() {
                // Ocultar el spinner y habilitar el botón nuevamente (eliminar estas líneas)
                // spinner.style.display = 'none';
                // generateTeamsBtn.disabled = false;
            });
    };

    // Event listener para el botón "Generar Equipos"
    generateTeamsBtn.addEventListener('click', generateTeams);

    // Event listener para el botón "Volver a Mezclar" / "Generar nuevos equipos" usando event delegation
    teamsResultDiv.addEventListener('click', function(event) {
        if (event.target && event.target.matches('button.remix-button')) {
            generateTeams();
        }
    });
});
</script>

<?php
// Incluir el footer
include('includes/footer.php');
?>
