<?php
session_start();

// Verifica si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirige al login si no ha iniciado sesión
    exit();
}

$user_id = $_SESSION['user_id'];

// Conexión a la base de datos
// Asegúrate de que db.php contenga la conexión a tu base de datos
require '../php/db.php';

// Verifica la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Consulta para obtener los datos del usuario
$sql_user = "SELECT username, nom, cognom, dataNaixement, localitzacio, descripcio, imagen_perfil FROM usuari WHERE id_user = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

if ($result_user->num_rows > 0) {
    $row_user = $result_user->fetch_assoc();
    $username = $row_user['username'];
    $nom = $row_user['nom'];
    $cognom = $row_user['cognom'];
    $dataNaixement = $row_user['dataNaixement'];
    $localitzacio = $row_user['localitzacio'];
    $descripcio_perfil = $row_user['descripcio'];
    // La ruta de la imagen de perfil: si la DB guarda 'uploads/...',
    // necesitamos añadir '../' para que sea accesible desde la carpeta 'html'.
    // Si la DB ya guarda '../uploads/...', entonces no necesitas añadir más.
    // Basado en tu código anterior, parece que la DB guarda 'uploads/...',
    // por lo que se añade '../' para que desde 'html/perfil.php' apunte a 'uploads/'.
    $imagen_perfil = $row_user['imagen_perfil'] ? '../' . htmlspecialchars($row_user['imagen_perfil']) : '../img/addImage.png';
} else {
    echo "Usuario no encontrado";
    exit();
}
$stmt_user->close();


// Consulta para obtener las publicaciones del usuario, obteniendo SOLO LA PRIMERA IMAGEN y el precio
$sql_publicaciones = "
    SELECT
        p.publicacion_id,
        p.titulo,
        p.precio,
        (SELECT gf.imagen
            FROM galeria_fotos gf
            WHERE gf.publicacion_id = p.publicacion_id
            ORDER BY gf.id_galeria ASC
            LIMIT 1) AS imagen_principal_ruta,
        p.fecha_creacion
    FROM
        publicacions p
    WHERE
        p.usuario_id = ?
    ORDER BY
        p.fecha_creacion DESC";

$stmt_publicaciones = $conn->prepare($sql_publicaciones);
$stmt_publicaciones->bind_param("i", $user_id);
$stmt_publicaciones->execute();
$result_publicaciones = $stmt_publicaciones->get_result();

$publicaciones = []; // Renombrada a $publicaciones para consistencia con mi código anterior
if ($result_publicaciones->num_rows > 0) {
    while($row_publicacion = $result_publicaciones->fetch_assoc()) {
        // Asumiendo que la ruta en la DB es algo como 'uploads/img/imagen.jpg'.
        // Si perfil.php está en 'html/', y 'uploads' en la raíz, entonces la ruta desde 'html/' es '../../uploads/img/imagen.jpg'.
        // Tu código anterior usaba $row_publicacion['imagen_ruta'] directamente sin añadir '../../'.
        // Si tu DB ya guarda '../uploads/img/imagen.jpg', entonces no necesitas añadir nada más.
        // Si guarda 'uploads/img/imagen.jpg', necesitas añadir '../' o '../../' dependiendo de la estructura.
        // Dado el último código que me enviaste que usa `$row_publicacion['imagen_ruta']` directamente,
        // asumo que en tu DB ya tienes las rutas ajustadas (ej. 'img/publicaciones/imagen.jpg' si la img está en img/publicaciones)
        // o que 'uploads' está a la misma altura que 'html'.
        // REGLA: Si perfil.php está en `proyecto_raiz/html/`, y uploads está en `proyecto_raiz/uploads/`,
        // la ruta correcta en el HTML sería `../../uploads/imagen.jpg`.
        // Si en la DB tienes `uploads/imagen.jpg`, necesitas `../../uploads/imagen.jpg`.
        // Si en la DB tienes `../uploads/imagen.jpg`, necesitas `../uploads/imagen.jpg`.
        // Mantendré la lógica de añadir '../../' para `imagen_principal_ruta` como la vez anterior,
        // ya que es la forma más común de acceder a un directorio padre.
        $imagen_publicacion = $row_publicacion['imagen_principal_ruta'] ? '../uploads/' . $row_publicacion['imagen_principal_ruta'] : '../img/default_post.png';

        $publicaciones[] = [
            'id_publicacion' => $row_publicacion['publicacion_id'],
            'titulo' => $row_publicacion['titulo'],
            'precio' => $row_publicacion['precio'],
            'imagen_principal_ruta' => htmlspecialchars($imagen_publicacion),
            'fecha_creacion' => date("d/m/Y", strtotime($row_publicacion['fecha_creacion']))
        ];
    }
}
$stmt_publicaciones->close();

// Datos para el menú de categorías
$categorias = [
    'Magic' => 'Magic: The Gathering',
    'Pokemon' => 'Pokémon',
    'One Piece' => 'One Piece',
    'Yu-Gi-Oh' => 'Yu-Gi-Oh!',
    'My Little Pony' => 'My Little Pony',
    'Invizimals' => 'Invizimals'
];

$conn->close();

// INICIO DEL CÓDIGO DEL POPUP:
// Verifica si hay un mensaje de éxito o error de la sesión (establecido en editar_perfil.php)
$perfil_actualizado_exito = null;
if (isset($_SESSION['perfil_actualizado_exito'])) {
    $perfil_actualizado_exito = $_SESSION['perfil_actualizado_exito'];
    unset($_SESSION['perfil_actualizado_exito']); // Limpia la variable de sesión para que no se muestre de nuevo
}

$perfil_actualizado_error = null;
if (isset($_SESSION['perfil_actualizado_error'])) {
    $perfil_actualizado_error = $_SESSION['perfil_actualizado_error'];
    unset($_SESSION['perfil_actualizado_error']); // Limpia la variable de sesión para que no se muestre de nuevo
}
// FIN DEL CÓDIGO DEL POPUP
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de <?php echo htmlspecialchars($username); ?> - CardCapture</title>
    <link rel="shortcut icon" href="../img/logo.png" />
    <link rel="stylesheet" href="../css/INDEXmain.css">
    <link rel="stylesheet" href="../css/cssPerfil.css">
    <link rel="stylesheet" href="../css/popup.css"> <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="headerx">
        <div class="logo">CARDCAPTURE</div>
        <div class="user-menu">
            <div class="iconx" id="userIcon">
                <img src="../img/user.png" class="user-icon" alt="Avatar de usuario">
            </div>
            <ul class="dropdown" id="dropdownMenu">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <li><a href="./InicioSesion.php">Iniciar Sesión</a></li>
                <?php else: ?>
                    <li><a href="./perfil.php">Perfil</a></li>
                    <li><a href="./meGusta.php">Favoritos</a></li>
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

    <main class="contenido-principal">
        <section class="profile-container">
            <div class="profile-summary">
                <img src="<?php echo htmlspecialchars($imagen_perfil); ?>" alt="Avatar de <?php echo htmlspecialchars($username); ?>" class="profile-avatar">
                <div class="profile-info">
                    <h1 class="profile-name"><?php echo htmlspecialchars($username); ?></h1>
                    <p class="profile-fullname"><?php echo htmlspecialchars($nom . " " . $cognom); ?></p>
                </div>
                <a href="./editar_perfil.php" class="button edit-profile">Editar Perfil ✎</a>
            </div>

            <div class="profile-details-compact">
                <p class="profile-bio"><?php echo nl2br(htmlspecialchars($descripcio_perfil ?: 'Sin descripción.')); ?></p>
                <div class="detail-group">
                    <span class="detail-label">Fecha de Nacimiento:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($dataNaixement ?: 'No especificada'); ?></span>
                </div>
                <div class="detail-group">
                    <span class="detail-label">Localización:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($localitzacio ?: 'No especificada'); ?></span>
                </div>
            </div>
        </section>

        <section class="user-posts-section">
            <h2>Mis Publicaciones</h2>
            <div class="posts-grid">
                <?php if (!empty($publicaciones)): ?>
                    <?php foreach ($publicaciones as $publicacion): ?>
                        <div class="post-card" onclick="location.href='editarPublicacion.php?id=<?php echo $publicacion['id_publicacion']; ?>'">
                            <img src="<?php echo htmlspecialchars($publicacion['imagen_principal_ruta']); ?>" alt="<?php echo htmlspecialchars($publicacion['titulo']); ?>">
                            <div class="post-card-content">
                                <h3><?php echo htmlspecialchars($publicacion['titulo']); ?></h3>
                                <p class="post-price"><?php echo htmlspecialchars(number_format($publicacion['precio'], 2)); ?> €</p>
                                <p>Publicado el: <?php echo htmlspecialchars($publicacion['fecha_creacion']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-posts-message">Aún no tienes publicaciones. ¡Anímate a subir tu primera carta!</p>
                <?php endif; ?>
            </div>

            <a href="publicaciones.php" class="add-post-button" title="Añadir nueva publicación">
                +
            </a>
        </section>
    </main>

    <footer id="footer">
        <canvas id="footerCanvas"></canvas>
        <div class="footer-container">
            <div class="footer-logo">
                <h2 id="footerTitle">CARDCAPTURE</h2>
                <p>Tu mercado definitivo para coleccionistas de cartas.</p>
            </div>
            <div class="footer-social">
                <h3>Síguenos</h3>
                <div class="social-icons">
                    <a href="https://www.facebook.com/" target="_blank"><img src="../img/Facebook.png" alt="Facebook"></a>
                    <a href="https://x.com/home?lang=es" target="_blank"><img src="../img/twitter.png" alt="Twitter"></a>
                    <a href="https://www.instagram.com/" target="_blank"><img src="../img/instagram.png" alt="Instagram"></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p id="footerText">&copy; 2025 CardCapture. Todos los derechos reservados.</p>
        </div>
    </footer>

    <div id="profileUpdatePopup" class="popup-container">
        <div class="popup-content">
            <span class="close-button" id="closePopup">&times;</span>
            <h2 id="popupTitle"></h2>
            <p id="popupMessage"></p>
            <button class="popup-button" id="okButton">Aceptar</button>
        </div>
    </div>

    <script src="../js/script.js"></script>
    <script src="../js/footer_animation.js"></script>
    <script src="../js/scriptHeader.js"></script>
    
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
            const logo = document.querySelector('.logo');

            window.addEventListener('scroll', () => {
                if (window.scrollY > header.offsetHeight) {
                    menuCategorias.classList.add('sticky');
                } else {
                    menuCategorias.classList.remove('sticky');
                }
            });

            // *** Lógica para volver a la página anterior al hacer clic en el logo ***
            logo.addEventListener('click', function() {
    window.location.href = '../index.php';
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

            // *** Lógica para mostrar el popup de actualización de perfil ***
            const profileUpdatePopup = document.getElementById('profileUpdatePopup');
            const closePopupButton = document.getElementById('closePopup');
            const okButton = document.getElementById('okButton');
            const popupTitle = document.getElementById('popupTitle');
            const popupMessage = document.getElementById('popupMessage');
            // La variable popupIcon ya no es necesaria aquí

            // Mensajes PHP pasados a JavaScript
            const successMessage = <?php echo json_encode($perfil_actualizado_exito); ?>;
            const errorMessage = <?php echo json_encode($perfil_actualizado_error); ?>;

            // Debugging: Ver qué valores llegan a JavaScript
            console.log("Success Message from PHP:", successMessage);
            console.log("Error Message from PHP:", errorMessage);

            if (successMessage) {
                popupTitle.textContent = "¡Perfil Actualizado!";
                popupMessage.innerHTML = successMessage; // Usar innerHTML para permitir <br>
                profileUpdatePopup.classList.add('show');
            } else if (errorMessage) {
                popupTitle.textContent = "Error al Actualizar Perfil";
                popupMessage.innerHTML = errorMessage; // Usar innerHTML para permitir <br>
                profileUpdatePopup.classList.add('show');
                profileUpdatePopup.classList.add('error'); // Añade clase para estilos de error
            }

            function closePopup() {
                profileUpdatePopup.classList.remove('show');
                profileUpdatePopup.classList.remove('error'); // Limpia la clase de error
                // Opcional: eliminar el contenido para que no se muestre si se vuelve a abrir sin motivo
                popupTitle.textContent = "";
                popupMessage.textContent = "";
            }

            closePopupButton.addEventListener('click', closePopup);
            okButton.addEventListener('click', closePopup);

            // Cerrar el popup si se hace clic fuera del contenido
            window.addEventListener('click', function(event) {
                if (event.target === profileUpdatePopup) {
                    closePopup();
                }
            });
        });
    </script>
</body>
</html>