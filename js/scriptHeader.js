document.addEventListener('DOMContentLoaded', () => {
    const userIcon = document.getElementById('userIcon');
    const dropdownMenu = document.getElementById('dropdownMenu');

    // Alternar visibilidad del menú al hacer clic en el icono
    userIcon.addEventListener('click', (event) => {
        event.stopPropagation(); // Evita que el evento se propague al documento
        dropdownMenu.classList.toggle('visible');
    });

    // Cerrar el menú al hacer clic en cualquier otra parte
    document.addEventListener('click', () => {
        dropdownMenu.classList.remove('visible');
    });

    // Prevenir el cierre del menú al hacer clic dentro de él
    dropdownMenu.addEventListener('click', (event) => {
        event.stopPropagation();
    });
});



