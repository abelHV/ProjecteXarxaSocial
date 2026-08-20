document.addEventListener('DOMContentLoaded', () => {
    const detallePublicacion = document.querySelector('.detalle-publicacion');

    // Animación de aparición
    detallePublicacion.style.opacity = 0;
    let opacity = 0;
    const intervalId = setInterval(() => {
        if (opacity >= 1) {
            clearInterval(intervalId);
        } else {
            opacity += 0.05;
            detallePublicacion.style.opacity = opacity;
        }
    }, 50);

    // Animación de hover para botones
    const botones = document.querySelectorAll('.volver-button, .chat-button');
    botones.forEach(boton => {
        boton.addEventListener('mouseover', () => {
            boton.style.transform = 'scale(1.05)';
        });

        boton.addEventListener('mouseout', () => {
            boton.style.transform = 'scale(1)';
        });
    });
});