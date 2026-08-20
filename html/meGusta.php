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

// Obtener las publicaciones favoritas del usuario si la sesión está iniciada
$favoritas = [];
$favoritas_ids = []; // Array para almacenar solo los IDs de las publicaciones favoritas para comprobación de "liked"
$error_message = null; // Inicializamos la variable de error para mensajes al usuario

if ($sesionIniciada && $user_id !== null) {
    try {
        $sql = "SELECT p.publicacion_id, p.titulo, p.categoria, p.precio, p.fecha_creacion
                FROM favoritos f
                JOIN publicacions p ON f.publicacion_id = p.publicacion_id
                WHERE f.usuario_id = ?
                ORDER BY f.fecha_agregado DESC"; // Ordena por la fecha en que se añadió a favoritos

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $favoritas = $result->fetch_all(MYSQLI_ASSOC);
            $favoritas_ids = array_column($favoritas, 'publicacion_id');
            $stmt->close();
        } else {
            error_log("Error al preparar la consulta de favoritos: " . $conn->error);
            $error_message = "Error al preparar la consulta. Por favor, inténtalo de nuevo más tarde.";
        }
    } catch (Exception $e) {
        error_log("Error al obtener favoritos: " . $e->getMessage());
        $error_message = "Error al cargar tus favoritos. Por favor, inténtalo de nuevo más tarde.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Favoritos - CardCapture</title>
    <link rel="shortcut icon" href="../img/logo.png" />

    <link rel="stylesheet" href="../css/INDEXmain.css">
    <link rel="stylesheet" href="../css/meGusta.css">
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
                    <li><a href="#">Favoritos</a></li>
                    <li><a href="./publicaciones.php">Vender</a></li>
                    <li><a href="./chat.php">Buzón</a></li>
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
            <img src="../img/corazonCarta.png" alt="Icono de Me Gusta" class="hero-image">
            <h1>Tus Publicaciones Favoritas</h1>
            <p>Aquí encontrarás todas las publicaciones que has marcado como favoritas.</p>
        </section>

        <section class="anuncios-grid">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php elseif (!empty($favoritas)): ?>
                <?php foreach ($favoritas as $index => $publicacion): ?>
                    <div class="anuncio-card fade-in" style="--animation-delay: <?php echo $index * 0.1; ?>s;">
                        <a href="./detalle_publicacion.php?id=<?php echo htmlspecialchars($publicacion['publicacion_id']); ?>" class="anuncio-link">
                            <div class="anuncio-imagen-wrapper">
                                <?php
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
                                ?>
                            </div>
                            <div class="anuncio-info-card">
                                <h3><?php echo htmlspecialchars($publicacion['titulo']); ?></h3>
                                <p class="precio"><?php echo htmlspecialchars(number_format($publicacion['precio'], 0, ',', '.')) . " €"; ?></p>
                                <p class="fecha-publicacion">Publicado el: <?php echo date('d/m/Y', strtotime($publicacion['fecha_creacion'])); ?></p>
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
                <p class="sin-favoritos">Aún no has añadido ninguna publicación a tus favoritos.</p>
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
    <script src="../js/script.js"></script> <script src="../js/footerAnimation.js"></script>
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
                        // Si se elimina una publicación de favoritos, recargar la página para que desaparezca
                        if (!isLiked) {
                            // Añadir una animación de salida antes de recargar
                            const card = button.closest('.anuncio-card');
                            if (card) {
                                card.classList.add('fade-out');
                                setTimeout(() => {
                                    location.reload();
                                }, 500); // Esperar a que la animación de salida termine
                            } else {
                                location.reload();
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error al actualizar favoritos:', error);
                    });
                });
            });
        });
    </script>
</body>
</html>