// assets/js/script.js

document.addEventListener('DOMContentLoaded', function() {
    // --- 1. Cambio de Tema (Dark/Light Mode) ---
    const themeToggle = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');
    const participantIcons = document.querySelectorAll('.theme-dependent-icon');

    function updateParticipantIcons(isDarkMode) {
        participantIcons.forEach(icon => {
            icon.src = isDarkMode ? 'assets/images/profile-white.svg' : 'assets/images/profile.svg';
        });
    }

    // Comprobar y manejar errores de LocalStorage
    let theme = 'dark';
    try {
        theme = localStorage.getItem('theme') || 'dark';
    } catch (e) {
        console.warn('LocalStorage no está disponible. Usando tema predeterminado oscuro.');
    }

    if (theme === 'dark') {
        document.body.classList.add('dark-mode');
        themeIcon.src = 'assets/images/sun.svg';
        updateParticipantIcons(true);
    } else {
        themeIcon.src = 'assets/images/moon.svg';
        updateParticipantIcons(false);
    }

    themeToggle.addEventListener('click', function() {
        document.body.classList.toggle('dark-mode');
        const isDarkMode = document.body.classList.contains('dark-mode');

        themeIcon.src = isDarkMode ? 'assets/images/sun.svg' : 'assets/images/moon.svg';
        updateParticipantIcons(isDarkMode);

        try {
            localStorage.setItem('theme', isDarkMode ? 'dark' : 'light');
        } catch (e) {
            console.warn('No se pudo guardar el tema en LocalStorage.');
        }
    });

    // --- 2. Menú Desplegable de Usuario ---
    const userMenuToggle = document.querySelector('.user-menu-toggle');
    const userMenu = document.querySelector('.user-menu');

    if (userMenuToggle && userMenu) {
        userMenuToggle.addEventListener('click', function(event) {
            event.stopPropagation();
            userMenu.classList.toggle('hidden');
        });

        // Ocultar menú al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!userMenuToggle.contains(e.target) && !userMenu.contains(e.target)) {
                userMenu.classList.add('hidden');
            }
        });
    }

    // --- 3. Filtros y Paginación con Axios ---
    const filterDeporte = document.getElementById('filter-deporte');
    const filterCiudad = document.getElementById('filter-ciudad');
    const eventosContainer = document.getElementById('eventos');
    const paginationContainer = document.querySelector('.pagination');

    function applyFilters() {
        const deporte = filterDeporte.value;
        const ciudad = filterCiudad.value.trim();

        const params = new URLSearchParams();
        if (deporte) params.append('deporte', deporte);
        if (ciudad) params.append('ciudad', ciudad);

        axios.get('index.php', {
            params: params
        })
        .then(response => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(response.data, 'text/html');

            // Actualizar eventos y paginación
            if (eventosContainer && doc.getElementById('eventos')) {
                eventosContainer.innerHTML = doc.getElementById('eventos').innerHTML;
            }
            if (paginationContainer && doc.querySelector('.pagination')) {
                paginationContainer.innerHTML = doc.querySelector('.pagination').innerHTML;
            }

            // Volver a vincular la paginación al filtro aplicado
            handlePagination(params);

            // Vincular eventos de eliminación después de actualizar el DOM
            bindDeleteButtons();
        })
        .catch(error => {
            console.error('Error al aplicar los filtros:', error);
        });
    }

    // Función para manejar la paginación y que los filtros se mantengan activos
    function handlePagination(params) {
        const paginationLinks = document.querySelectorAll('.pagination a');
        paginationLinks.forEach(link => {
            link.addEventListener('click', function(event) {
                event.preventDefault();
                const url = new URL(link.href, window.location.origin);
                params.set('page', url.searchParams.get('page'));  // Mantener filtros en la paginación

                axios.get('index.php', {
                    params: params
                })
                .then(response => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(response.data, 'text/html');

                    // Actualizar eventos y paginación
                    if (eventosContainer && doc.getElementById('eventos')) {
                        eventosContainer.innerHTML = doc.getElementById('eventos').innerHTML;
                    }
                    if (paginationContainer && doc.querySelector('.pagination')) {
                        paginationContainer.innerHTML = doc.querySelector('.pagination').innerHTML;
                    }

                    // Volver a vincular la paginación al filtro
                    handlePagination(params);

                    // Vincular eventos de eliminación después de actualizar el DOM
                    bindDeleteButtons();
                })
                .catch(error => {
                    console.error('Error al aplicar la paginación:', error);
                });
            });
        });
    }

    // Ejecutar filtros al presionar el botón de búsqueda
    window.applyFilters = applyFilters;

    // --- 4. Unirse a Eventos usando AJAX ---
    function joinEvent(eventId) {
        console.log('Unirse al evento ID:', eventId);
        axios.get('join_event.php', { params: { id: eventId } })
            .then(response => {
                const data = response.data;

                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Te has unido al evento!',
                        showConfirmButton: false,
                        timer: 1500
                    });

                    // Actualizar la lista de participantes en el DOM
                    const participantList = document.querySelector(`#event-${eventId} .participant-list ul`);
                    if (participantList) {
                        const newParticipant = document.createElement('li');
                        const link = document.createElement('a');
                        link.href = `profile_view.php?id=${data.user_id}`;
                        link.textContent = data.username;
                        newParticipant.appendChild(link);
                        participantList.appendChild(newParticipant);
                    }

                    // Actualizar el número de participantes
                    const participantCount = document.querySelector(`#event-${eventId} .participants p`);
                    if (participantCount) {
                        participantCount.innerHTML = `<img src="assets/images/profile.svg" alt="Participantes" class="participantes theme-dependent-icon"> ${data.new_count}`;
                    }

                    // Cambiar el botón de "Unirse al Evento" por un mensaje de éxito
                    const joinButton = document.querySelector(`#event-${eventId} .join-event`);
                    if (joinButton) {
                        joinButton.outerHTML = '<p>Te has unido al evento.</p>';
                    }
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error en fetch:', error);
                Swal.fire('Error', 'Ocurrió un problema al unirse al evento', 'error');
            });
    }

    // --- 5. Eliminar Eventos usando AJAX ---
    function bindDeleteButtons() {
        const deleteButtons = document.querySelectorAll('.delete-event');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const eventId = this.getAttribute('data-event-id');
                if (eventId) {
                    confirmDeletion(eventId);
                }
            });
        });
    }

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

    function deleteEvent(eventId) {
        console.log('Eliminando evento ID:', eventId);
        // Realizar una petición POST a delete_event.php
        axios.post('delete_event.php', new URLSearchParams({ event_id: eventId }))
            .then(response => {
                const data = response.data;
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

    // --- 6. Generar Equipos usando AJAX ---
    function generateTeams() {
        const form = document.getElementById('team-form');
        const formData = new FormData(form);
        const teamsResultDiv = document.getElementById('teams-result');

        // Mostrar un indicador de carga
        teamsResultDiv.innerHTML = "<p>Generando equipos...</p>";
        teamsResultDiv.style.display = 'block';

        axios.post('team_generator_process.php', formData)
            .then(response => {
                teamsResultDiv.innerHTML = response.data;
                teamsResultDiv.style.display = 'block';
            })
            .catch(error => {
                console.error('Error:', error);
                teamsResultDiv.innerHTML = "<p style='color:red;'>Ocurrió un error al generar los equipos.</p>";
                teamsResultDiv.style.display = 'block';
            });
    }

    // Vincular el botón "Generar Equipos" con la función correspondiente
    const generateTeamsBtn = document.getElementById('generate-teams-btn');
    if (generateTeamsBtn) {
        generateTeamsBtn.addEventListener('click', function () {
            generateTeams();
        });
    }

    // --- 7. Mostrar/Ocultar la lista de participantes ---
    function bindParticipantListToggle() {
        document.querySelectorAll('.participants').forEach(function(element) {
            element.addEventListener('mouseover', function() {
                const participantList = this.querySelector('.participant-list');
                if (participantList) {
                    participantList.style.display = 'block';
                }
            });
            element.addEventListener('mouseout', function() {
                const participantList = this.querySelector('.participant-list');
                if (participantList) {
                    participantList.style.display = 'none';
                }
            });
        });
    }

    // Inicializar los event listeners al cargar el DOM
    bindDeleteButtons();
    bindParticipantListToggle();
});
