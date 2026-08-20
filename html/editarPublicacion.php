<?php
session_start();
// Verifica si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$publicacion_id = $_GET['id'] ?? null; // Obtener el ID de la publicación de la URL

// Redirige si no hay ID de publicación
if (!$publicacion_id) {
    header("Location: perfil.php");
    exit();
}

require '../php/db.php'; // Conexión a la base de datos

// --- Lógica para obtener los datos de la publicación ---
$publicacion = null;
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener datos de la publicación y verificar que pertenece al usuario
$sql_publicacion = "SELECT * FROM publicacions WHERE publicacion_id = ? AND usuario_id = ?";
$stmt_publicacion = $conn->prepare($sql_publicacion);
if ($stmt_publicacion) {
    $stmt_publicacion->bind_param("ii", $publicacion_id, $user_id);
    $stmt_publicacion->execute();
    $result_publicacion = $stmt_publicacion->get_result();
    if ($result_publicacion->num_rows > 0) {
        $publicacion = $result_publicacion->fetch_assoc();
    } else {
        // Si la publicación no existe o no pertenece al usuario, redirigir
        $_SESSION['error_message'] = "Publicación no encontrada o no tienes permiso para editarla.";
        header("Location: perfil.php");
        exit();
    }
    $stmt_publicacion->close();
} else {
    die("Error preparando la consulta de publicación: " . $conn->error);
}

// --- Manejo de la Modificación y Eliminación de la Publicación (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == 'update_publicacion') {
        $titulo = htmlspecialchars(trim($_POST['titulo']));
        $descripcion = htmlspecialchars(trim($_POST['descripcion']));
        $precio = floatval($_POST['precio']);
        $categoria = htmlspecialchars($_POST['categoria']); // <-- Aquí se obtiene la categoría
        $ubicacion = htmlspecialchars(trim($_POST['ubicacion']));
        $estado = htmlspecialchars($_POST['estado']);
        
       

        // Recuperar latitud y longitud del formulario
        $latitud = isset($_POST['latitud']) && $_POST['latitud'] !== '' ? floatval($_POST['latitud']) : null;
        $longitud = isset($_POST['longitud']) && $_POST['longitud'] !== '' ? floatval($_POST['longitud']) : null;

        // --- DEBUGGING: Mostrar el valor de la categoría recibida ---
        error_log("DEBUG: Valor de categoria recibido para update: " . $categoria);

        // Validar que la categoría sea una válida según el ENUM
        $categorias_validas = ['Magic', 'Pokemon', 'One Piece', 'Yu-Gi-Oh!', 'My Little Pony', 'Invizimals'];
        if (!in_array($categoria, $categorias_validas)) {
            error_log("ERROR: Categoría inválida - Valor recibido: " . $categoria);
            $_SESSION['publicacion_actualizada_error'] = "Categoría inválida. Por favor, selecciona una categoría válida.";
            header("Location: perfil.php");
            exit();
        }

        // Actualizar datos de la publicación en la tabla 'publicacions'
        $sql_update = "UPDATE publicacions SET 
            titulo = ?, 
            descripcion = ?, 
            precio = ?, 
            categoria = ?, 
            ubicacion = ?, 
            estado = ?, 
            latitud = ?, 
            longitud = ?, 
            fecha_actualizacion = NOW() 
            WHERE publicacion_id = ? AND usuario_id = ?";
        $stmt_update = $conn->prepare($sql_update);

        if ($stmt_update) {
            $stmt_update->bind_param("ssdsssddii", 
                $titulo, 
                $descripcion, 
                $precio, 
                $categoria, 
                $ubicacion, 
                $estado, 
                $latitud, 
                $longitud, 
                $publicacion_id, 
                $user_id
            );

            
                
            if ($stmt_update->execute()) {
                $_SESSION['publicacion_actualizada_exito'] = "Publicación actualizada exitosamente.";
          
                                 header("Location: perfil.php");

                exit();
            } else {
                error_log("ERROR: Error al actualizar la publicación: " . $stmt_update->error);
                $_SESSION['publicacion_actualizada_error'] = "Error al actualizar la publicación: " . $stmt_update->error;
                header("Location: perfil.php");
                exit();
            }
            $stmt_update->close();
        } else {
            $_SESSION['publicacion_actualizada_error'] = "Error preparando la consulta de actualización: " . $conn->error;
            header("Location: perfil.php");
            exit();
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == 'delete_publicacion') {
        // --- Lógica para eliminar la publicación por completo ---
        // 1. Obtener rutas de imágenes para eliminar archivos físicos
        $images_to_delete_paths = [];
        $sql_get_all_imgs = "SELECT imagen FROM galeria_fotos WHERE publicacion_id = ?";
        $stmt_get_all_imgs = $conn->prepare($sql_get_all_imgs);
        if ($stmt_get_all_imgs) {
            $stmt_get_all_imgs->bind_param("i", $publicacion_id);
            $stmt_get_all_imgs->execute();
            $result_all_imgs = $stmt_get_all_imgs->get_result();
            while ($row_img = $result_all_imgs->fetch_assoc()) {
                if (strpos($row_img['imagen'], '/') === 0) {
                    $images_to_delete_paths[] = '.' . $row_img['imagen'];
                } else {
                    $images_to_delete_paths[] = '../' . $row_img['imagen'];
                }
            }
            $stmt_get_all_imgs->close();
        } else {
            error_log("Error preparando SELECT de todas las imágenes para eliminar: " . $conn->error);
        }

        // 2. Eliminar entradas relacionadas en 'chats'
        $sql_delete_chats = "DELETE FROM chats WHERE id_publicacion = ?";
        $stmt_delete_chats = $conn->prepare($sql_delete_chats);
        if ($stmt_delete_chats) {
            $stmt_delete_chats->bind_param("i", $publicacion_id);
            $stmt_delete_chats->execute();
            $stmt_delete_chats->close();
        }

        // 3. Eliminar entradas relacionadas en 'favoritos'
        $sql_delete_fav = "DELETE FROM favoritos WHERE publicacion_id = ?";
        $stmt_delete_fav = $conn->prepare($sql_delete_fav);
        if ($stmt_delete_fav) {
            $stmt_delete_fav->bind_param("i", $publicacion_id);
            $stmt_delete_fav->execute();
            $stmt_delete_fav->close();
        }

        // 4. Eliminar entradas relacionadas en 'visitas_publicacion'
        $sql_delete_visitas = "DELETE FROM visitas_publicacion WHERE publicacion_id = ?";
        $stmt_delete_visitas = $conn->prepare($sql_delete_visitas);
        if ($stmt_delete_visitas) {
            $stmt_delete_visitas->bind_param("i", $publicacion_id);
            $stmt_delete_visitas->execute();
            $stmt_delete_visitas->close();
        }

        // 5. Eliminar entradas en 'galeria_fotos'
        $sql_delete_imgs_db = "DELETE FROM galeria_fotos WHERE publicacion_id = ?";
        $stmt_delete_imgs_db = $conn->prepare($sql_delete_imgs_db);
        if ($stmt_delete_imgs_db) {
            $stmt_delete_imgs_db->bind_param("i", $publicacion_id);
            $stmt_delete_imgs_db->execute();
            $stmt_delete_imgs_db->close();
        }

        // 6. Eliminar archivos físicos
        foreach ($images_to_delete_paths as $file_path) {
            if (file_exists($file_path)) {
                if (!unlink($file_path)) {
                    error_log("Fallo al eliminar el archivo físico: " . $file_path);
                }
            }
        }

        // 7. Eliminar la publicación principal
        $sql_delete_pub = "DELETE FROM publicacions WHERE publicacion_id = ? AND usuario_id = ?";
        $stmt_delete_pub = $conn->prepare($sql_delete_pub);
        if ($stmt_delete_pub) {
            $stmt_delete_pub->bind_param("ii", $publicacion_id, $user_id);
            if ($stmt_delete_pub->execute()) {
                $_SESSION['publicacion_eliminada_exito'] = "Publicación eliminada exitosamente.";
                header("Location: perfil.php");
                exit();
            } else {
                $_SESSION['publicacion_eliminada_error'] = "Error al eliminar la publicación: " . $stmt_delete_pub->error;
                header("Location: perfil.php");
                exit();
            }
            $stmt_delete_pub->close();
        } else {
            $_SESSION['publicacion_eliminada_error'] = "Error preparando DELETE de publicación principal: " . $conn->error;
            header("Location: perfil.php");
            exit();
        }
    }
}

// Datos para el menú de categorías (mantener consistencia)
$categorias = [
    'Magic' => 'Magic: The Gathering',
    'Pokemon' => 'Pokémon',
    'One Piece' => 'One Piece',
    'Yu-Gi-Oh!' => 'Yu-Gi-Oh!',
    'My Little Pony' => 'My Little Pony',
    'Invizimals' => 'Invizimals'
];

// Datos para el estado de la publicación
$estados_publicacion = [
    'nuevo' => 'Nuevo',
    'como_nuevo' => 'Como Nuevo',
    'regular' => 'Regular',
    'mal' => 'Mal'
];

// Obtener datos del usuario para la imagen de perfil
$imagen_perfil_actual = '../img/default_avatar.png';
$sql_user_img = "SELECT imagen_perfil FROM usuari WHERE id_user = ?";
$stmt_user_img_header = $conn->prepare($sql_user_img);
if ($stmt_user_img_header) {
    $stmt_user_img_header->bind_param("i", $user_id);
    $stmt_user_img_header->execute();
    $result_user_img = $stmt_user_img_header->get_result();
    if ($row_user_img = $result_user_img->fetch_assoc()) {
        if (!empty($row_user_img['imagen_perfil'])) {
            $imagen_perfil_actual = '../' . htmlspecialchars($row_user_img['imagen_perfil']);
        }
    }
    $stmt_user_img_header->close();
}

$conn->close();

// Obtener la localización inicial de la publicación o un valor por defecto
$initial_location = htmlspecialchars($publicacion['ubicacion'] ?? 'Montornès del Vallès');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Publicación - CardCapture</title>
    <link rel="shortcut icon" href="../img/logo.png" />
    <link rel="stylesheet" href="../css/INDEXmain.css">
    <link rel="stylesheet" href="../css/cssPerfil.css">
    <link rel="stylesheet" href="../css/cssEditarPublicacion.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDG95fZTPRuh52kKfnhZnyF79E1Kv9KwcM&libraries=places&callback=initMap"></script>
    <style>
        /* Estilos básicos para mensajes de feedback (estos son solo para el caso de error si no se redirige) */
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* Estilos para el contenedor del mapa */
        #map {
            width: 100%;
            height: 350px;
            margin-top: 10px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        }

        .map-help-text {
            font-size: 0.9em;
            color: #aaa;
            margin-top: 5px;
        }

        /* Estilos para los textarea y inputs en general */
        .form-group textarea,
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select {
            width: calc(100% - 20px); /* Ancho completo menos padding */
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #555;
            border-radius: 5px;
            background-color: #333;
            color: #eee;
            font-size: 1em;
            box-sizing: border-box; /* Incluye padding y border en el ancho */
        }

        .form-group textarea {
            resize: vertical; /* Permitir redimensionar verticalmente */
            min-height: 80px;
        }

        .form-group label {
            color: #eee;
            margin-bottom: 5px;
            display: block;
            font-weight: bold;
        }

        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }

        .save-button, .delete-button {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .save-button {
            background-color: #28a745; /* Verde para guardar */
            color: white;
        }
        .save-button:hover {
            background-color: #218838;
        }

        .delete-button {
            background-color: #dc3545; /* Rojo para eliminar */
            color: white;
        }
        .delete-button:hover {
            background-color: #c82333;
        }

        /* Estilos para el popup de confirmación de eliminación */
        .confirm-popup-overlay {
            display: none; /* ¡Oculto por defecto! */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            justify-content: center;
            align-items: center;
            z-index: 1000; /* Asegura que esté por encima de otros elementos */
        }

        .confirm-popup-content {
            background-color: #222;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 400px;
            width: 90%;
            color: #eee;
        }

        .confirm-popup-content h3 {
            margin-top: 0;
            color: #eee;
            font-size: 1.5em;
            margin-bottom: 20px;
        }

        .confirm-popup-content p {
            margin-bottom: 25px;
            line-height: 1.5;
            color: #ccc;
        }

        .confirm-buttons button {
            padding: 10px 20px;
            margin: 0 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .btn-cancel {
            background-color: #6c757d;
            color: white;
        }

        .btn-cancel:hover {
            background-color: #5a6268;
        }

        .btn-confirm-delete {
            background-color: #dc3545;
            color: white;
        }

        .btn-confirm-delete:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <header class="headerx">
        <div class="logo" id="mainLogo"> CARDCAPTURE
        </div>
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
        <div class="edit-post-container">
            <h1>Editar Publicación</h1>

            <?php
            // Estos mensajes de feedback se eliminan o son redundantes si siempre rediriges a perfil.php
            // Se mantienen por si hubiera un error grave ANTES de la redirección.
            if (isset($_SESSION['publicacion_actualizada_exito_temp'])): ?>
                <div class="message success"><?php echo $_SESSION['publicacion_actualizada_exito_temp']; unset($_SESSION['publicacion_actualizada_exito_temp']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['publicacion_actualizada_error_temp'])): ?>
                <div class="message error"><?php echo $_SESSION['publicacion_actualizada_error_temp']; unset($_SESSION['publicacion_actualizada_error_temp']); ?></div>
            <?php endif; ?>

            <?php if ($publicacion): // Solo mostrar el formulario si la publicación se cargó ?>
            <form action="editarPublicacion.php?id=<?php echo htmlspecialchars($publicacion_id); ?>" method="POST">
                <input type="hidden" name="action" value="update_publicacion">

                <div class="form-group">
                    <label for="titulo">Título:</label>
                    <input type="text" id="titulo" name="titulo" value="<?php echo htmlspecialchars($publicacion['titulo']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción:</label>
                    <textarea id="descripcion" name="descripcion" required><?php echo htmlspecialchars($publicacion['descripcion']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="precio">Precio (€):</label>
                    <input type="number" id="precio" name="precio" step="0.01" min="0" value="<?php echo htmlspecialchars($publicacion['precio']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="categoria">Categoría:</label>
                    <select id="categoria" name="categoria" required>
                        <?php foreach ($categorias as $key => $value): ?>
                            <option value="<?php echo htmlspecialchars($key); ?>" <?php echo ($publicacion['categoria'] == $key) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($value); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="ubicacion">Ubicación:</label>
                    <input type="text" id="ubicacion" name="ubicacion" value="<?php echo $initial_location; ?>" placeholder="Buscar ubicación..." autocomplete="off">
                </div>

                <div id="map"></div>
                <input type="hidden" id="latitud" name="latitud" value="<?php echo htmlspecialchars($publicacion['latitud'] ?? ''); ?>">
                <input type="hidden" id="longitud" name="longitud" value="<?php echo htmlspecialchars($publicacion['longitud'] ?? ''); ?>">
                <p class="map-help-text">
                    Usa el campo de búsqueda o arrastra el marcador en el mapa para establecer tu ubicación.
                </p>

                <div class="form-group">
                    <label for="estado">Estado:</label>
                    <select id="estado" name="estado" required>
                        <?php foreach ($estados_publicacion as $key => $value): ?>
                            <option value="<?php echo htmlspecialchars($key); ?>" <?php echo ($publicacion['estado'] == $key) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($value); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="save-button">Guardar Cambios</button>
                    <button type="button" class="delete-button" id="deletePostBtn">Eliminar Publicación</button>
                </div>
            </form>
            <?php else: ?>
                <p class="message error">No se pudo cargar la información de la publicación para editar.</p>
            <?php endif; ?>
        </div>
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
                    <a href="https://www.facebook.com/" target="_blank"><img class="icon" src="../img/Facebook.png" alt="Facebook"></a>
                    <a href="https://x.com/home?lang=es" target="_blank"><img class="icon" src="../img/twitter.png" alt="Twitter"></a>
                    <a href="https://www.instagram.com/" target="_blank"><img class="icon" src="../img/instagram.png" alt="Instagram"></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p id="footerText">© <?php echo date("Y"); ?> CardCapture. Todos los derechos reservados.</p>
        </div>
    </footer>

    <div id="confirmDeletePopup" class="confirm-popup-overlay">
        <div class="confirm-popup-content">
            <h3>Confirmar Eliminación</h3>
            <p>¿Estás seguro de que quieres eliminar esta publicación? Esta acción es irreversible y eliminará todos los datos relacionados (imágenes, favoritos, visitas, chats).</p>
            <div class="confirm-buttons">
                <button class="btn-cancel" id="cancelDeleteBtn">Cancelar</button>
                <form action="editarPublicacion.php?id=<?php echo htmlspecialchars($publicacion_id); ?>" method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="delete_publicacion">
                    <button type="submit" class="btn-confirm-delete">Sí, Eliminar</button>
                </form>
            </div>
        </div>
    </div>


    <script src="../js/script.js"></script>
    <script src="../js/footer_animation.js"></script>
    <script src="../js/scriptHeader.js"></script>
    <script>
        let map;
        let marker;
        let autocomplete;
        let geocoder;

        function initMap() {
            geocoder = new google.maps.Geocoder();

            const ubicacionInput = document.getElementById('ubicacion');
            const latitudInput = document.getElementById('latitud');
            const longitudInput = document.getElementById('longitud');

            let initialCoords = { lat: 41.5647, lng: 2.2599 }; // Coordenadas por defecto (Montornès del Vallès)
            let initialZoom = 15;

            if (latitudInput.value && longitudInput.value) {
                initialCoords = {
                    lat: parseFloat(latitudInput.value),
                    lng: parseFloat(longitudInput.value)
                };
                createMapAndMarker(initialCoords, initialZoom);
            } else {
                const initialLocationText = ubicacionInput.value.trim();
                if (initialLocationText) {
                    geocoder.geocode({ 'address': initialLocationText }, function(results, status) {
                        if (status === 'OK' && results[0]) {
                            initialCoords = {
                                lat: results[0].geometry.location.lat(),
                                lng: results[0].geometry.location.lng()
                            };
                            latitudInput.value = initialCoords.lat;
                            longitudInput.value = initialCoords.lng;
                        } else {
                            console.warn('Geocodificación inicial fallida para: ' + initialLocationText + ' - Status: ' + status);
                            latitudInput.value = initialCoords.lat;
                            longitudInput.value = initialCoords.lng;
                        }
                        createMapAndMarker(initialCoords, initialZoom);
                    });
                } else {
                    latitudInput.value = initialCoords.lat;
                    longitudInput.value = initialCoords.lng;
                    createMapAndMarker(initialCoords, initialZoom);
                }
            }
        }

        function createMapAndMarker(coords, zoom) {
            map = new google.maps.Map(document.getElementById('map'), {
                center: coords,
                zoom: zoom
            });

            marker = new google.maps.Marker({
                map: map,
                position: coords,
                draggable: true
            });

            const ubicacionInput = document.getElementById('ubicacion');
            autocomplete = new google.maps.places.Autocomplete(ubicacionInput);
            autocomplete.bindTo('bounds', map);

            autocomplete.addListener('place_changed', function() {
                marker.setVisible(false);
                const place = autocomplete.getPlace();

                if (!place.geometry) {
                    ubicacionInput.classList.add('error');
                    console.warn("No details available for input '" + place.name + "'");
                    return;
                } else {
                    ubicacionInput.classList.remove('error');
                    map.setCenter(place.geometry.location);
                    map.setZoom(15);
                    marker.setPosition(place.geometry.location);
                    marker.setVisible(true);
                    updateLocationFieldsFromCoords(place.geometry.location);
                }
            });

            marker.addListener('dragend', function() {
                updateLocationFieldsFromCoords(marker.getPosition());
            });
        }

        function updateLocationFieldsFromCoords(latlng) {
            geocoder.geocode({ location: latlng }, function(results, status) {
                if (status === 'OK') {
                    if (results[0]) {
                        document.getElementById('ubicacion').value = results[0].formatted_address;
                        document.getElementById('latitud').value = latlng.lat();
                        document.getElementById('longitud').value = latlng.lng();
                        console.log('Latitud:', latlng.lat(), 'Longitud:', latlng.lng());
                    } else {
                        console.warn('No se encontraron resultados para la geocodificación inversa.');
                        document.getElementById('latitud').value = '';
                        document.getElementById('longitud').value = '';
                        document.getElementById('ubicacion').value = '';
                    }
                } else {
                    console.error('Fallo el geocodificador debido a: ' + status);
                    document.getElementById('latitud').value = '';
                    document.getElementById('longitud').value = '';
                    document.getElementById('ubicacion').value = '';
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Lógica para el Dropdown de Usuario
            const userIcon = document.getElementById('userIcon');
            const dropdownMenu = document.getElementById('dropdownMenu');

            function toggleDropdown() {
                dropdownMenu.classList.toggle('show');
            }

            userIcon.addEventListener('click', toggleDropdown);

            window.addEventListener('click', function(event) {
                if (!event.target.matches('#userIcon') && !event.target.matches('.user-icon')) {
                    if (dropdownMenu.classList.contains('show')) {
                        dropdownMenu.classList.remove('show');
                    }
                }
            });

            // Lógica para el Menú Hamburguesa (Categorías)
            const hamburgerBtn = document.getElementById('hamburgerBtn');
            const menuLista = document.getElementById('menuLista');

            hamburgerBtn.addEventListener('click', function() {
                menuLista.classList.toggle('show-menu');
                hamburgerBtn.classList.toggle('open');
            });

            document.addEventListener('click', function(event) {
                if (!menuLista.contains(event.target) && !hamburgerBtn.contains(event.target)) {
                    if (menuLista.classList.contains('show-menu')) {
                        menuLista.classList.remove('show-menu');
                        hamburgerBtn.classList.remove('open');
                    }
                }
            });

            // Lógica del Popup de Confirmación de Eliminación
            const deletePostBtn = document.getElementById('deletePostBtn');
            const confirmDeletePopup = document.getElementById('confirmDeletePopup');
            const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');

            if (deletePostBtn) {
                deletePostBtn.addEventListener('click', function(event) {
                    event.preventDefault();
                    confirmDeletePopup.style.display = 'flex';
                });
            }

            if (cancelDeleteBtn) {
                cancelDeleteBtn.addEventListener('click', function() {
                    confirmDeletePopup.style.display = 'none';
                });
            }

            if (confirmDeletePopup) {
                confirmDeletePopup.addEventListener('click', function(event) {
                    if (event.target === confirmDeletePopup) {
                        confirmDeletePopup.style.display = 'none';
                    }
                });
            }

            // Lógica para el logo "CARDCAPTURE" (volver atrás)
            const mainLogo = document.getElementById('mainLogo'); // Selecciona el div del logo
            if (mainLogo) {
                mainLogo.style.cursor = 'pointer'; // Opcional: para indicar que es clicable
                mainLogo.addEventListener('click', function() {
                    window.history.back(); // Vuelve a la página anterior en el historial del navegador
                });
            }
        });
    </script>
    <script src="../js/script.js"></script>
</body>
</html>