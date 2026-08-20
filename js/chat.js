document.addEventListener('DOMContentLoaded', () => {
    const enviarBtn = document.getElementById('enviar-btn');
    const mensajeInput = document.getElementById('mensaje-input');
    const mensajesContainer = document.querySelector('.mensajes');

    enviarBtn.addEventListener('click', () => {
        const mensaje = mensajeInput.value;
        if (mensaje.trim() !== '') {
            // Aquí puedes agregar la lógica para enviar el mensaje al servidor y actualizar la interfaz de usuario
            console.log('Mensaje enviado:', mensaje);
            mensajeInput.value = '';
        }
    });

    // Aquí puedes agregar la lógica para actualizar los mensajes del chat periódicamente
    setInterval(() => {
        // Aquí puedes agregar la lógica para obtener los nuevos mensajes del servidor y actualizar la interfaz de usuario
        console.log('Actualizando mensajes...');
    }, 5000); // Actualizar cada 5 segundos
});