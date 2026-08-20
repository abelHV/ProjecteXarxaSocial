<?php
session_start();
$sesionIniciada = isset($_SESSION['user_id']);

// Incluir el archivo de conexión a la base de datos
include '../php/db.php';

// Verificar si el usuario ha iniciado sesión
if (!$sesionIniciada) {
    header('Location: InicioSesion.html');
    exit;
}

$usuario_id = $_SESSION['user_id'];

// Obtener el chat_id de la URL
$chat_id = isset($_GET['chat_id']) ? $_GET['chat_id'] : null;

// Ruta a la imagen por defecto para perfiles
$default_profile_image = '../img/addImage.png'; // Asegúrate de que esta ruta sea correcta

if ($chat_id) {
    // --- Lógica para mostrar el chat individual ---
    // Obtener la información del chat
    $sql_chat = "SELECT c.usuario1_id, c.usuario2_id, p.publicacion_id, p.titulo AS titulo_publicacion,
                                 u1.username AS usuario1_nombre, u1.imagen_perfil AS usuario1_imagen,
                                 u2.username AS usuario2_nombre, u2.imagen_perfil AS usuario2_imagen
                                 FROM chats c
                                 JOIN publicacions p ON c.id_publicacion = p.publicacion_id
                                 JOIN usuari u1 ON c.usuario1_id = u1.id_user
                                 JOIN usuari u2 ON c.usuario2_id = u2.id_user
                                 WHERE c.chat_id = $chat_id";
    $result_chat = $conn->query($sql_chat);

    if ($result_chat && $result_chat->num_rows > 0) {
        $chat = $result_chat->fetch_assoc();

        // Determinar el usuario con el que se está hablando
        $otro_usuario_id = ($chat['usuario1_id'] == $usuario_id) ? $chat['usuario2_id'] : $chat['usuario1_id'];
        $otro_usuario_nombre = ($chat['usuario1_id'] == $usuario_id) ? $chat['usuario2_nombre'] : $chat['usuario1_nombre'];
        
        // **CAMBIO APLICADO AQUÍ**: Construir la ruta completa de la imagen del otro usuario, usando la imagen por defecto si es null
        $otro_usuario_imagen_db = ($chat['usuario1_id'] == $usuario_id) ? $chat['usuario2_imagen'] : $chat['usuario1_imagen'];
        $otro_usuario_imagen = $otro_usuario_imagen_db ? '../' . htmlspecialchars($otro_usuario_imagen_db) : $default_profile_image;

        // Obtener la primera imagen de la galería de la publicación
        $sql_primera_imagen = "SELECT imagen FROM galeria_fotos WHERE publicacion_id = " . $chat['publicacion_id'] . " LIMIT 1";
        $result_primera_imagen = $conn->query($sql_primera_imagen);
        if ($result_primera_imagen && $result_primera_imagen->num_rows > 0) {
            $primera_imagen_row = $result_primera_imagen->fetch_assoc();
            $imagen_publicacion = '../uploads/' . $primera_imagen_row['imagen']; // Asumiendo que las imágenes de publicaciones también necesitan '../'
        } else {
            $imagen_publicacion = "https://placehold.co/400x300/EEE/31343C"; // Imagen por defecto si no hay en la galería
        }

        // Obtener los mensajes del chat
        $mensajes = [];
        $sql_mensajes = "SELECT m.*, u.username, u.imagen_perfil AS emisor_imagen_perfil
                                 FROM mensajes m
                                 JOIN usuari u ON m.usuario_id = u.id_user
                                 WHERE m.chat_id = $chat_id
                                 ORDER BY m.fecha_envio ASC";
        $result_mensajes = $conn->query($sql_mensajes);
        if ($result_mensajes) {
            while ($row_mensaje = $result_mensajes->fetch_assoc()) {
                // **CAMBIO APLICADO AQUÍ**: Construir la ruta completa de la imagen del emisor del mensaje, usando la imagen por defecto si es null
                $emisor_imagen_db = $row_mensaje['emisor_imagen_perfil'];
                $row_mensaje['emisor_imagen_perfil'] = $emisor_imagen_db ? '../' . htmlspecialchars($emisor_imagen_db) : $default_profile_image;
                $mensajes[] = $row_mensaje;
            }
        }
        // Marcar mensajes como leídos
        $sql_marcar_leidos = "UPDATE mensajes SET leido = 1 WHERE chat_id = $chat_id AND usuario_id != $usuario_id";
        $conn->query($sql_marcar_leidos);

    } else {
        // Si no se encuentra el chat, establecer variables a null o mostrar un mensaje de error
        $chat = null;
        $mensajes = [];
        echo "Chat no encontrado.";
    }
} else {
    // --- Lógica para mostrar la lista de chats (Bústia) ---
    $sql_chats = "SELECT c.chat_id, u.username, u.imagen_perfil, p.titulo AS titulo_publicacion, u2.username AS otro_usuario_nombre, u2.imagen_perfil AS otro_usuario_imagen, c.usuario1_id, c.usuario2_id,
                                 (SELECT contenido FROM mensajes WHERE chat_id = c.chat_id ORDER BY fecha_envio DESC LIMIT 1) as ultimo_mensaje,
                                 (SELECT fecha_envio FROM mensajes WHERE chat_id = c.chat_id ORDER BY fecha_envio DESC LIMIT 1) as ultima_fecha_envio
                                 FROM chats c
                                 JOIN usuari u ON (c.usuario1_id = u.id_user OR c.usuario2_id = u.id_user)
                                 JOIN usuari u2 ON (c.usuario1_id = u2.id_user OR c.usuario2_id = u2.id_user) AND u2.id_user != $usuario_id
                                 JOIN publicacions p ON c.id_publicacion = p.publicacion_id
                                 WHERE (c.usuario1_id = $usuario_id OR c.usuario2_id = $usuario_id)
                                 GROUP BY c.chat_id
                                 ORDER BY ultima_fecha_envio DESC"; // Ordenar por fecha del último mensaje

    $result_chats = $conn->query($sql_chats);
    $chats = [];
    if ($result_chats) {
        while ($row_chat = $result_chats->fetch_assoc()) {
            // Determinar el ID del otro usuario para obtener su imagen de perfil
            $otro_usuario_id_chat = ($row_chat['usuario1_id'] == $usuario_id) ? $row_chat['usuario2_id'] : $row_chat['usuario1_id'];
            // Obtener la imagen de perfil del otro usuario
            $sql_otro_usuario_imagen = "SELECT imagen_perfil FROM usuari WHERE id_user = $otro_usuario_id_chat";
            $result_otro_usuario_imagen = $conn->query($sql_otro_usuario_imagen);
            if ($result_otro_usuario_imagen && $result_otro_usuario_imagen->num_rows > 0) {
                $row_otro_usuario_imagen = $result_otro_usuario_imagen->fetch_assoc();
                // **CAMBIO APLICADO AQUÍ**: Usar la imagen por defecto si el path de la base de datos es null
                $row_chat['otro_usuario_imagen'] = $row_otro_usuario_imagen['imagen_perfil'] ? '../' . htmlspecialchars($row_otro_usuario_imagen['imagen_perfil']) : $default_profile_image;
            } else {
                // **CAMBIO APLICADO AQUÍ**: Si no se encuentra la imagen en la BD, usar la imagen por defecto
                $row_chat['otro_usuario_imagen'] = $default_profile_image;
            }

            // Aseguramos que el 'otro_usuario_nombre' sea del otro usuario.
            $row_chat['otro_usuario_nombre'] = ($row_chat['username'] == $_SESSION['username']) ? $row_chat['otro_usuario_nombre'] : $row_chat['username'];
            $chats[] = $row_chat;
        }
    } else {
        $chats = [];
    }
}

$categorias = [
    'Magic' => 'Magic: The Gathering',
    'Pokemon' => 'Pokémon',
    'One Piece' => 'One Piece',
    'Yu-Gi-Oh' => 'Yu-Gi-Oh!',
    'My Little Pony' => 'My Little Pony',
    'Invizimals' => 'Invizimals'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($chat_id) ? 'Chat con ' . htmlspecialchars($otro_usuario_nombre) : 'Buzón'; ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap">
            <link rel="shortcut icon" href="../img/logo.png" />

    <link rel="stylesheet" href="../css/INDEXmain.css">
    <link rel="stylesheet" href="../css/cssChat.css">
</head>
<body>
    <header class="headerx">
        <div class="logo">CARDCAPTURE</div>
        <div class="user-menu">
            <div class="iconx" id="userIcon">
                <img src="../img/user.png" class="user-icon" alt="">
            </div>
            <ul class="dropdown" id="dropdownMenu">
                <?php if (!$sesionIniciada): ?>
                    <li><a href="./InicioSesion.html">Iniciar Sesión</a></li>
                <?php else: ?>
                    <li><a href="./perfil.php">Perfil</a></li>
                    <li><a href="#">Favoritos</a></li>
                    <li><a href="./publicaciones.php">Vender</a></li>
                    <li><a href="./chat.php">Buzón</a></li>
                    <li><a href="../php/logout.php">Cerrar Sesión</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </header>

    <nav class="menu-categorias" id="menuCategorias">
        <button class="hamburger-btn" id="hamburgerBtn">
            <div class="bar"></div>
            <div class="bar"></div>
            <div class="bar"></div>
        </button>
        <ul class="menu-lista" id="menuLista">
            <?php foreach ($categorias as $clave => $nombre): ?>
                <li><a href="../html/inicio.php?categoria=<?php echo urlencode($clave); ?>"><?php echo htmlspecialchars($nombre); ?></a></li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <main class="<?php echo ($chat_id) ? 'chat-principal' : 'bústia-principal'; ?>">
        <?php if ($chat_id && $chat != null): ?>
            <div class="chat-layout">
                <div class="product-info-sidebar">
    <a href="detalle_publicacion.php?id=<?php echo $chat['publicacion_id']; ?>">
        <img src="<?php echo $imagen_publicacion; ?>" alt="Imagen de la publicación">
    </a>
    <h3><?php echo $chat['titulo_publicacion']; ?></h3>
    <?php if (!empty($chat['descripcion_publicacion'])): ?>
        <p><?php echo htmlspecialchars(substr($chat['descripcion_publicacion'], 0, 50)) . (strlen($chat['descripcion_publicacion']) > 50 ? '...' : ''); ?></p>
    <?php endif; ?>
</div>
                <div class="chat-container">
                    <div class="usuarios">
                        <div class="usuario">
                            <img src="<?php echo $otro_usuario_imagen; ?>" alt="Imagen de perfil de <?php echo $otro_usuario_nombre; ?>">
                            <div class="usuario-info">
                                <h3><?php echo $otro_usuario_nombre; ?></h3>
                                <p><?php echo $chat['titulo_publicacion']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="mensajes" id="mensajes-container">
                        <?php if (isset($mensajes) && is_array($mensajes)): ?>
                            <?php foreach ($mensajes as $mensaje): ?>
                                <div class="mensaje <?php echo ($mensaje['usuario_id'] == $usuario_id) ? 'mensaje-propio' : 'mensaje-ajeno'; ?>">
                                    <div class="mensaje-body">
                                        <p><?php echo $mensaje['contenido']; ?></p>
                                        <span class="mensaje-fecha"><?php echo date('H:i', strtotime($mensaje['fecha_envio'])); ?></span>
                                        <?php if ($mensaje['leido'] == 1 && $mensaje['usuario_id'] == $usuario_id): ?>
                                            <span class="mensaje-leido">Leído</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No hay mensajes en este chat.</p>
                        <?php endif; ?>
                    </div>
                    <div class="enviar-mensaje">
                        <input type="text" id="mensaje-input" placeholder="Escribe un mensaje...">
                        <button id="enviar-btn">Enviar</button>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="chat-list">
                <h2>Buzón
                                <img src="../img/buzon.png" alt="Icono Buzón"> 

                </h2>
                <?php if (!empty($chats)): ?>
                    <?php foreach ($chats as $chat): ?>
                        <a href="chat.php?chat_id=<?php echo $chat['chat_id']; ?>" class="chat-item">
                            <div class="chat-item-avatar">
                                <img src="<?php echo $chat['otro_usuario_imagen']; ?>" alt="Imagen de perfil de <?php echo htmlspecialchars($chat['otro_usuario_nombre']); ?>">
                            </div>
                            <div class="chat-item-info">
                                <h3><?php echo htmlspecialchars($chat['otro_usuario_nombre']); ?></h3>
                                <p><?php echo htmlspecialchars($chat['titulo_publicacion']); ?></p>
                            </div>
                            <p class="chat-item-last-message">
                                <?php echo htmlspecialchars(substr($chat['ultimo_mensaje'], 0, 30)) . (strlen($chat['ultimo_mensaje']) > 30 ? '...' : ''); ?>
                            </p>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No tienes ninguna conversación activa.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer id="footer">
        <canvas id="footerCanvas"></canvas>
        <div class="footer-container">
            <div class="footer-logo">
                <h2>CARDCAPTURE</h2>
                <p>Tu mercado definitivo para coleccionistas de cartas.</p>
            </div>
            <div class="footer-social">
                <h3>Síguenos</h3>
                <div class="social-icons">
                    <a href="#"><img src="../img/Facebook.png" alt="Facebook"></a>
                    <a href="#"><img src="../img/instagram.png" alt="Instagram"></a>
                    <a href="#"><img src="../img/twitter.png" alt="Twitter"></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p id="footerText">&copy; 2025 CardCapture. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script>
        document.getElementById('enviar-btn').addEventListener('click', function() {
            var mensaje = document.getElementById('mensaje-input').value;
            var chat_id = <?php echo $chat_id; ?>;
            var mensajesContainer = document.getElementById('mensajes-container');

            var xhr = new XMLHttpRequest();
            xhr.open('POST', '../php/enviar_mensaje.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 400) {
                    // El mensaje se envió correctamente
                    location.reload();
                    //mensajesContainer.scrollTop = mensajesContainer.scrollHeight; // Hacer scroll al final
                } else {
                    console.error('Error al enviar el mensaje:', xhr.status, xhr.statusText);
                }
            };
            xhr.onerror = function() {
                console.error('Error de red al enviar el mensaje');
            };
            xhr.send('chat_id=' + encodeURIComponent(chat_id) + '&mensaje=' + encodeURIComponent(mensaje));
            document.getElementById('mensaje-input').value = '';

        });
        // Función para hacer scroll al final de los mensajes
        function scrollToBottom() {
            var mensajesContainer = document.getElementById('mensajes-container');
            if (mensajesContainer) { // Añadida comprobación para asegurar que el elemento existe
                mensajesContainer.scrollTop = mensajesContainer.scrollHeight;
            }
        }

        // Llamar a la función scrollToBottom() al cargar la página y después de agregar nuevos mensajes
        window.onload = scrollToBottom;
    </script>
    <script src="../js/script.js"></script>
    <script src="../js/footer_animation.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // *** Lógica para el Dropdown de Usuario ***
            const userIcon = document.getElementById('userIcon');
            const dropdownMenu = document.getElementById('dropdownMenu');

            function toggleDropdown() {
                dropdownMenu.classList.toggle('open');
            }

            userIcon.addEventListener('click', toggleDropdown);

            document.addEventListener('click', function(event) {
                const isClickInside = userIcon.contains(event.target) || dropdownMenu.contains(event.target);
                if (!isClickInside && dropdownMenu.classList.contains('open')) {
                    dropdownMenu.classList.remove('open');
                }
            });

            // *** Lógica para el Menú de Categorías Sticky ***
            const menuCategorias = document.getElementById('menuCategorias');
            const header = document.querySelector('.headerx');
            const logo = document.querySelector('.logo'); // Seleccionamos el elemento
            window.addEventListener('scroll', () => {
                if (window.scrollY > header.offsetHeight) {
                    menuCategorias.classList.add('sticky');
                } else {
                    menuCategorias.classList.remove('sticky');
                }
            });

            // *** Lógica para volver a la página anterior al hacer clic en el logo ***
            logo.addEventListener('click', function() {
                window.history.back();
            });

            // *** Lógica para el Menú Hamburguesa ***
            const hamburgerBtn = document.getElementById('hamburgerBtn');
            const menuLista = document.getElementById('menuLista');

            function toggleMenu() {
                menuLista.classList.toggle('open');
            }

            hamburgerBtn.addEventListener('click', toggleMenu);

            document.addEventListener('click', function(event) {
                const isClickInside = hamburgerBtn.contains(event.target) || menuLista.contains(event.target);
                if (!isClickInside && menuLista.classList.contains('open')) {
                    menuLista.classList.remove('open');
                }
            });
        });
    </script>
    <script src="../js/scriptHeader.js"></script>
    <script src="../js/footerAnimation.js"></script>
</body>
</html>

<?php $conn->close(); ?>