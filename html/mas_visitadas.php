<?php
session_start();
require '../php/db.php'; // Asegúrate de que esta ruta sea correcta y el archivo db.php establezca la conexión a la base de datos.

$sesionIniciada = isset($_SESSION['user_id']);
$user_id = $sesionIniciada ? $_SESSION['user_id'] : null;

// Definición de categorías para el menú de navegación
$categorias = [
    'Magic' => 'Magic: The Gathering',
    'Pokemon' => 'Pokémon',
    'One Piece' => 'One Piece',
    'Yu-Gi-Oh' => 'Yu-Gi-Oh!',
    'My Little Pony' => 'My Little Pony',
    'Invizimals' => 'Invizimals'
];

// Obtener las 20 publicaciones más visitadas
$publicaciones_mas_visitadas = [];
$favoritas_ids = []; // Array para almacenar solo los IDs de las publicaciones favoritas para comprobación de "liked"
$error_message = null; // Inicializamos la variable de error para mensajes al usuario

// Añadir un check si la conexión $conn es válida
if ($conn->connect_error) {
    $error_message = "No se pudo conectar a la base de datos: " . $conn->connect_error;
    error_log("Error de conexión en mas_visitadas.php: " . $conn->connect_error);
} else {
    try {
        // MODIFICACIÓN: Consulta para filtrar publicaciones del usuario actual
        $sql_mas_visitadas = "SELECT publicacion_id, titulo, categoria, precio, fecha_creacion, visualizacion, usuario_id
                              FROM publicacions"; // Añadimos usuario_id a la SELECT para el filtro

        // Si el usuario está logueado, excluimos sus propias publicaciones
        if ($sesionIniciada && $user_id !== null) {
            $sql_mas_visitadas .= " WHERE usuario_id != ?"; // Se añade la condición WHERE
        }

        $sql_mas_visitadas .= " ORDER BY visualizacion DESC LIMIT 20";

        $stmt_mas_visitadas = $conn->prepare($sql_mas_visitadas);
        if ($stmt_mas_visitadas) {
            // Si el usuario está logueado, vinculamos el parámetro
            if ($sesionIniciada && $user_id !== null) {
                $stmt_mas_visitadas->bind_param("i", $user_id);
            }

            if ($stmt_mas_visitadas->execute()) {
                $result_mas_visitadas = $stmt_mas_visitadas->get_result();
                if ($result_mas_visitadas) { // Check if get_result was successful
                    $publicaciones_mas_visitadas = $result_mas_visitadas->fetch_all(MYSQLI_ASSOC);
                } else {
                    $error_message = "Error al obtener resultados de las publicaciones más visitadas.";
                    error_log("Error al obtener resultados de publicaciones más visitadas: " . $stmt_mas_visitadas->error);
                }
            } else {
                $error_message = "Error al ejecutar la consulta de publicaciones más visitadas.";
                error_log("Error al ejecutar la consulta de publicaciones más visitadas: " . $stmt_mas_visitadas->error);
            }
            $stmt_mas_visitadas->close();
        } else {
            $error_message = "Error al preparar la consulta de publicaciones más visitadas.";
            error_log("Error al preparar la consulta de publicaciones más visitadas: " . $conn->error);
        }
    } catch (Exception $e) {
        $error_message = "Error inesperado al cargar las publicaciones más visitadas.";
        error_log("Excepción al obtener las publicaciones más visitadas: " . $e->getMessage());
    }

    // Obtener las publicaciones favoritas del usuario si la sesión está iniciada (para mostrar el botón de "Me gusta" correctamente)
    if ($sesionIniciada && $user_id !== null) {
        try {
            $sql_favoritas = "SELECT publicacion_id FROM favoritos WHERE usuario_id = ?";
            $stmt_favoritas = $conn->prepare($sql_favoritas);
            if ($stmt_favoritas) {
                $stmt_favoritas->bind_param("i", $user_id);
                if ($stmt_favoritas->execute()) {
                    $result_favoritas = $stmt_favoritas->get_result();
                    if ($result_favoritas) {
                        $favoritas_ids = array_column($result_favoritas->fetch_all(MYSQLI_ASSOC), 'publicacion_id');
                    } else {
                        error_log("Error al obtener resultados de favoritos: " . $stmt_favoritas->error);
                    }
                } else {
                    error_log("Error al ejecutar la consulta de favoritos: " . $stmt_favoritas->error);
                }
                $stmt_favoritas->close();
            } else {
                error_log("Error al preparar la consulta de favoritos para publicaciones más visitadas: " . $conn->error);
            }
        } catch (Exception $e) {
            error_log("Excepción al obtener favoritos para publicaciones más visitadas: " . $e->getMessage());
        }
    }
} // End if ($conn->connect_error) else block
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publicaciones Más Visitadas - CardCapture</title>
    <link rel="shortcut icon" href="../img/logo.png" />

    <link rel="stylesheet" href="../css/INDEXmain.css">
    <link rel="stylesheet" href="../css/mas_visitadas.css">
</head>
<body>
    <header class="headerx">
        <div class="logo">CARDCAPTURE</div>
        <div class="user-menu">
            <div class="iconx" id="userIcon">
                <img src="../img/user.png" class="user-icon" alt="Icono de usuario">
            </div>
            <ul class="dropdown" id="dropdownMenu">
                <?php if (!$sesionIniciada): ?>
                    <li><a href="./InicioSesion.php">Iniciar Sesión</a></li>
                <?php else: ?>
                    <li><a href="./perfil.php">Perfil</a></li>
                    <li><a href="./meGusta.php">Favoritos</a></li>
                    <li><a href="./publicaciones.php">Vender</a></li>
                    <li><a href="./chat.php">Buzon</a></li>
                    <li><a href="../php/logout.php">Cerrar Sesión</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </header>

    <nav class="menu-categorias" id="menuCategorias">
        <button class="hamburger-btn" id="hamburgerBtn" aria-label="Abrir menú de categorías">
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

    <main class="contenido-principal">
        <section class="hero">
            <img src="../img/estrella.png" alt="Icono de visualizaciones" class="hero-image">
            <h1>Publicaciones Más Visitadas</h1>
            <p>Descubre las 20 publicaciones más populares de CardCapture.</p>
        </section>

        <section class="anuncios-grid">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger" style="color: #e74c3c; background-color: #fce4e4; border: 1px solid #e74c3c; padding: 15px; margin: 20px auto; border-radius: 8px; text-align: center; max-width: 600px;">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php elseif (!empty($publicaciones_mas_visitadas)): ?>
                <?php foreach ($publicaciones_mas_visitadas as $index => $publicacion): ?>
                    <div class="anuncio-card fade-in" style="--animation-delay: <?php echo $index * 0.1; ?>s;">
                        <a href="./detalle_publicacion.php?id=<?php echo htmlspecialchars($publicacion['publicacion_id']); ?>" class="anuncio-link">
                            <div class="anuncio-imagen-wrapper">
                                <?php
                                // Ensure $conn is valid before preparing this statement
                                if ($conn && !$conn->connect_error) {
                                    $sql_primera_imagen = "SELECT imagen FROM galeria_fotos WHERE publicacion_id = ? ORDER BY id_galeria ASC LIMIT 1";
                                    $stmt_imagen = $conn->prepare($sql_primera_imagen);
                                    if ($stmt_imagen) {
                                        $stmt_imagen->bind_param("i", $publicacion['publicacion_id']);
                                        $stmt_imagen->execute();
                                        $result_imagen = $stmt_imagen->get_result();
                                        if ($result_imagen && $result_imagen->num_rows > 0) {
                                            $ruta_primera_imagen = $result_imagen->fetch_assoc()['imagen'];
                                            echo "<img src='" . htmlspecialchars($ruta_primera_imagen) . "' alt='" . htmlspecialchars($publicacion['titulo']) . "'>";
                                        } else {
                                            echo "<img src='../img/placeholder.png' alt='Sin imagen'>";
                                        }
                                        $stmt_imagen->close();
                                    } else {
                                        error_log("Error al preparar la consulta de la primera imagen: " . $conn->error);
                                        echo "<img src='../img/placeholder.png' alt='Error al cargar imagen'>";
                                    }
                                } else {
                                    echo "<img src='../img/placeholder.png' alt='Error de conexión a la BD'>";
                                }
                                ?>
                            </div>
                            <div class="anuncio-info-card">
                                <h3><?php echo htmlspecialchars($publicacion['titulo']); ?></h3>
                                <div class="price-date-group">
                                    <p class="precio"><?php echo htmlspecialchars(number_format($publicacion['precio'], 0, ',', '.')) . " €"; ?></p>
                                </div>
                                <div>
                                                                        <p class="fecha-publicacion">Publicado el: <?php echo date('d/m/Y', strtotime($publicacion['fecha_creacion'])); ?></p>

                                </div>
                            </div>
                        </a>
                        <?php if ($sesionIniciada): ?>
                            <button class='like-button <?php echo in_array($publicacion['publicacion_id'], $favoritas_ids) ? 'liked' : ''; ?>' data-publicacion-id='<?php echo htmlspecialchars($publicacion['publicacion_id']); ?>' aria-label="Me gusta">
                                <svg class='heart-icon' viewBox='0 0 32 29.6'>
                                    <path d='M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2c6.1-9.3,16-11.8,16-21.2C32,3.8,28.2,0,23.6,0z'/>
                                </svg>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <?php if (!isset($error_message)): // Solo muestra este mensaje si no hubo un error específico y la lista está vacía ?>
                    <p class="sin-publicaciones">No se encontraron publicaciones populares.</p>
                <?php endif; ?>
            <?php endif; ?>
        </section>
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

    <script src="../js/scriptHeader.js"></script>
    <script src="../js/script.js"></script>
    <script src="../js/footerAnimation.js"></script>
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
            const logo = document.querySelector('.logo'); // Seleccionamos el elemento con la clase "logo"

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

            // *** Lógica para los botones de "Me gusta" (favoritos) ***
            const likeButtons = document.querySelectorAll('.like-button');

            likeButtons.forEach(button => {
                button.addEventListener('click', function(event) {
                    event.preventDefault(); // Evita el comportamiento predeterminado del botón
                    this.classList.toggle('liked'); // Alterna la clase 'liked' para cambiar el color del corazón
                    const publicacionId = this.dataset.publicacionId;
                    const isLiked = this.classList.contains('liked'); // Verdadero si ahora está "liked", falso si se ha "desgustado"

                    // Añadir una pequeña animación al hacer click en el corazón
                    const heartIcon = this.querySelector('.heart-icon');
                    heartIcon.classList.add('animate-heart');
                    setTimeout(() => {
                        heartIcon.classList.remove('animate-heart');
                    }, 300); // Duración de la animación

                    // Envía la solicitud AJAX al script PHP para actualizar favoritos
                    fetch('../php/favoritos.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `publicacion_id=${publicacionId}&accion=${isLiked ? 'agregar' : 'eliminar'}`
                    })
                    .then(response => response.text()) // O .json() si tu script PHP devuelve JSON
                    .then(data => {
                        console.log(data); // Para depuración, muestra la respuesta del servidor
                        // En esta página, no recargamos al "desgustar" porque la publicación seguiría en la lista.
                        // Solo nos aseguramos de que el estado visual cambie.
                    })
                    .catch(error => {
                        console.error('Error al actualizar favoritos:', error);
                        // Si hay un error, puedes revertir el estado visual del botón si lo deseas.
                        this.classList.toggle('liked');
                    });
                });
            });
        });
    </script>
</body>
</html>