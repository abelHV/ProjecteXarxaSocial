<?php
// InicioSesion.php
session_start(); // Inicia la sesión para acceder a las variables $_SESSION

// Inicializa las variables para almacenar los mensajes flash
$flash_status = null;
$flash_message = null;

// Comprueba si hay un mensaje flash en la sesión y lo recupera
if (isset($_SESSION['flash_status']) && isset($_SESSION['flash_message'])) {
    $flash_status = $_SESSION['flash_status'];
    $flash_message = $_SESSION['flash_message'];
    // IMPORTANTE: Limpia las variables de sesión inmediatamente después de leerlas
    // para que el popup no aparezca de nuevo al recargar la página.
    unset($_SESSION['flash_status']);
    unset($_SESSION['flash_message']);
}

// Determinar si debemos aplicar la animación de entrada al formulario
// La animación solo se aplicará si NO hay un mensaje de error (indicando que es una carga "limpia")
// Vuelvo a añadir esta lógica si quieres que el formulario se anime al cargar por primera vez
$apply_login_animation = ($flash_status !== 'error');

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title>Iniciar Sessió - CardCapture</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="shortcut icon" href="../img/logo.png" />
    <link rel="stylesheet" href="../css/cssSesion.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <a href="./../index.php" class="home-icon-button" title="Volver a la página principal">
        <i class="fas fa-home"></i>
    </a>

    <div id="statusPopup" class="status-popup"></div>

    <div class="login-container <?php echo $apply_login_animation ? 'animated-on-load' : ''; ?>">
        <h1>CARDCAPTURE</h1>
        <form action="../php/login.php" method="POST">
            <div class="input-group">
                <label for="username">Nombre de Usuario</label>
                <input type="text" id="username" name="username" required />
            </div>
            <div class="input-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required />
            </div>
            <div class="button-group">
                <button type="submit" class="botones">Iniciar Sesión</button>
                <button type="button" onclick="window.location.href='./Registro.html'" class="botones">Registrarse</button>
            </div>
            <div class="forgotPasswd">
                <a href="#" onclick="resetPasswd()" id="forgotPasswd">¿Has olvidado la contraseña?</a>
                <script>
                    function resetPasswd() {
                        let userInput = prompt("Introduce tu correo electrónico o nombre de usuario:");
                        if (userInput) {
                            fetch("../php/resetPasswordSend.php", {
                                method: "POST",
                                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                                body: "email=" + encodeURIComponent(userInput)
                            })
                            .then(response => response.text())
                            .then(data => alert(data))
                            .catch(error => alert("Error al enviar la solicitud"));
                        } else {
                            alert("Por favor, introduce tu correo electrónico o nombre de usuario.");
                        }
                    }
                </script>
            </div>
        </form>
    </div>
    <div class="card-animation"></div>
    <script src="../js/script2.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const statusPopup = document.getElementById('statusPopup');
            const loginContainer = document.querySelector('.login-container'); // Mantenemos esta referencia

            // Función para mostrar el popup
            function showPopup(message, type) {
                statusPopup.textContent = message;
                statusPopup.className = `status-popup active ${type}`; // Añade active y type (success/error)

                // El popup se oculta solo después de 3 segundos
                setTimeout(() => {
                    statusPopup.classList.remove('active'); // Inicia animación de salida
                    setTimeout(() => {
                        statusPopup.textContent = ''; // Limpia el texto
                        statusPopup.classList.remove(type); // Remueve la clase de tipo
                    }, 500); // Espera la duración de la transición de salida (0.5s)
                }, 3000); // Muestra el popup por 3 segundos
            }

            // --- CÓDIGO CORREGIDO: Leer de variables PHP en lugar de URLParams ---
            const flashStatus = "<?php echo $flash_status; ?>";
            const flashMessage = "<?php echo $flash_message; ?>";

            if (flashMessage) { // Si hay un mensaje flash (sea éxito o error)
                showPopup(flashMessage, flashStatus);
            }
            // --- FIN CÓDIGO CORREGIDO ---


            // REESTABLECEMOS LA LÓGICA ORIGINAL DE ANIMACIÓN DEL FORMULARIO
            // PARA QUE NO INTERFIERA CON EL POPUP Y HAGA LO QUE HACÍA ANTES.
            const applyLoginAnimation = "<?php echo $apply_login_animation ? 'true' : 'false'; ?>";

            if (applyLoginAnimation === 'false') {
                // Si la animación no debe aplicarse (por ejemplo, después de un error),
                // asegúrate de que el login-container ya esté visible y en su posición final
                loginContainer.style.opacity = '1';
                loginContainer.style.transform = 'translateY(0)';
                // Y remueve la clase animated-on-load para que no intente animar
                loginContainer.classList.remove('animated-on-load');
            } else {
                // Si la animación debe aplicarse, se encarga el CSS con animated-on-load
                loginContainer.classList.add('animated-on-load');
            }

            // Opcional: Para evitar que la animación se repita si el usuario navega
            // internamente o recarga sin un mensaje de error/éxito específico
            // Puedes usar localStorage para marcar que la página ya se cargó una vez.
            // if (!localStorage.getItem('loginPageLoadedOnce')) {
            //     loginContainer.classList.add('animated-on-load');
            //     localStorage.setItem('loginPageLoadedOnce', 'true');
            // } else {
            //     loginContainer.style.opacity = '1';
            //     loginContainer.style.transform = 'translateY(0)';
            // }
            // Considera cómo quieres resetear esto (ej. al cerrar la sesión).
        });
    </script>
</body>
</html>