document.addEventListener('DOMContentLoaded', () => {
    const heroImageContainer = document.querySelector('.hero-image');
    const imageNames = [
        '../img/reverseMLP.png',
        '../img/reverseMTG.png',
        '../img/reverseOP.png',
        '../img/reversePKMN.png',
        '../img/reverseYu.png'
    ];
    let currentIndex = 0;
    const cambioInterval = 3000; // Intervalo de cambio en milisegundos
    let cartaImagen;

    // Estilos para la carta
    const cartaStyle = `
        max-width: 200px;
        max-height: 260px;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(1);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        opacity: 1;
        transition: opacity 0.8s ease-in-out, transform 0.8s ease-in-out;
    `;

    // Inicializar la carta
    cartaImagen = document.createElement('img');
    cartaImagen.src = `./img/${imageNames[currentIndex]}`;
    cartaImagen.alt = 'Carta destacada';
    cartaImagen.style.cssText = cartaStyle;
    heroImageContainer.appendChild(cartaImagen);
    heroImageContainer.style.position = 'relative';
    heroImageContainer.style.minHeight = '260px'; // Asegura espacio para la carta

    function cambiarCarta() {
        // Desvanecer la carta actual y reducir su tamaño
        cartaImagen.style.opacity = 0;
        cartaImagen.style.transform = 'translate(-50%, -50%) scale(0.8)';

        // Esperar la transición y cambiar la imagen
        setTimeout(() => {
            currentIndex = (currentIndex + 1) % imageNames.length;
            cartaImagen.src = `./img/${imageNames[currentIndex]}`;

            // Hacer aparecer la nueva carta y restaurar su tamaño
            cartaImagen.style.opacity = 1;
            cartaImagen.style.transform = 'translate(-50%, -50%) scale(1)';
        }, 800); // Debe coincidir con la duración de la transición CSS
    }

    // Iniciar el cambio automático de cartas
    setInterval(cambiarCarta, cambioInterval);
});