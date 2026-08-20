<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
require_once('../php/funciones_perfil.php');

$datosUsuario = obtenerDatosUsuario($user_id);
if (!$datosUsuario) {
    echo "Usuario no encontrado";
    exit();
}
$imagen_perfil_actual = $datosUsuario['imagen_perfil'];

// Lógica unificada para GUARDAR TODOS LOS CAMBIOS (foto y datos)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guardar_todo'])) {
    $mensaje_exito = [];
    $mensaje_error = [];

    // 1. Intentar actualizar la foto de perfil si se sube una nueva
    if (isset($_FILES['nueva_imagen']) && $_FILES['nueva_imagen']['error'] === UPLOAD_ERR_OK) {
        $resultado_foto = actualizarFotoPerfil($user_id, $_FILES['nueva_imagen']);
        if ($resultado_foto === true) {
            $mensaje_exito[] = "Foto de perfil actualizada con éxito.";
            // Volver a obtener los datos del usuario para reflejar la nueva imagen
            $datosUsuario = obtenerDatosUsuario($user_id);
            $imagen_perfil_actual = $datosUsuario['imagen_perfil'];
        } else {
            $mensaje_error[] = "Error al actualizar la foto de perfil: " . $resultado_foto;
        }
    }

    // 2. Intentar actualizar los datos del usuario
    $resultado_datos = actualizarDatosUsuario(
        $user_id,
        $_POST['nombre'],
        $_POST['apellido'],
        $_POST['fecha_nacimiento'],
        $_POST['localizacion'], // Este campo ahora viene del autocompletado/mapa
        $_POST['descripcion']
    );

    if ($resultado_datos === true) {
        $mensaje_exito[] = "Datos del perfil actualizados con éxito.";
    } else {
        $mensaje_error[] = "Error al actualizar los datos del perfil: " . $resultado_datos;
    }

    // *** CAMBIO AQUÍ: Usar variable de sesión para el mensaje de éxito ***
    if (!empty($mensaje_exito)) {
        $_SESSION['perfil_actualizado_exito'] = implode("<br>", $mensaje_exito); // Guardamos el mensaje
    }
    if (!empty($mensaje_error)) {
        $_SESSION['perfil_actualizado_error'] = implode("<br>", $mensaje_error); // Guardamos los errores
    }
    
    // Redirigir a perfil.php después de establecer la sesión
    header("Location: perfil.php");
    exit();
}


$sesionIniciada = isset($_SESSION['user_id']);
$categorias = [
    'Magic' => 'Magic: The Gathering',
    'Pokemon' => 'Pokémon',
    'One Piece' => 'One Piece',
    'Yu-Gi-Oh' => 'Yu-Gi-Oh!',
    'My Little Pony' => 'My Little Pony',
    'Invizimals' => 'Invizimals'
];

// Obtener la localización inicial del usuario o un valor por defecto
$initial_location = htmlspecialchars($datosUsuario['localitzacio'] ?: 'Montornès del Vallès');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - CardCapture</title>
    <link rel="shortcut icon" href="../img/logo.png" />

    <link rel="stylesheet" href="../css/INDEXmain.css">
    <link rel="stylesheet" href="../css/cssPerfil.css">
    <link rel="stylesheet" href="../css/cssEditarPerfil.css"> <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDG95fZTPRuh52kKfnhZnyF79E1Kv9KwcM&libraries=places&callback=initMap"></script>
</head>
<body>
    <header class="headerx">
        <div class="logo">CARDCAPTURE</div>
        <div class="user-menu">
            <div class="iconx" id="userIcon">
                <img src="../img/user.png" class="user-icon" alt="Avatar de usuario">
            </div>
            <ul class="dropdown" id="dropdownMenu">
                <?php if (!$sesionIniciada): ?>
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
        <div class="edit-profile-container">
            <h1 class="edit-profile-title">Editar Perfil</h1>
            
            <form action="editar_perfil.php" method="post" enctype="multipart/form-data" class="edit-profile-form">
                
                <div class="profile-image-container">
                    <img src="<?php echo $imagen_perfil_actual ? '../' . htmlspecialchars($imagen_perfil_actual) : '../img/addImage.png'; ?>" alt="Foto de perfil" class="profile-image">
                    <input type="file" name="nueva_imagen" id="nueva_imagen" accept="image/*">
                    <div class="upload-buttons">
                        <label for="nueva_imagen">Seleccionar Nueva Imagen</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="nombre">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($datosUsuario['nom']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="apellido">Apellido:</label>
                    <input type="text" id="apellido" name="apellido" value="<?php echo htmlspecialchars($datosUsuario['cognom']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="fecha_nacimiento">Fecha de Nacimiento:</label>
                    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="<?php echo htmlspecialchars($datosUsuario['dataNaixement']); ?>" >
                </div>
                
                <div class="form-group">
                    <label for="localizacion">Localización:</label>
                    <input type="text" id="localizacion" name="localizacion" value="<?php echo $initial_location; ?>" placeholder="Buscar ubicación..." autocomplete="off">
                </div>
                
                <div id="map"></div> <input type="hidden" id="latitud" name="latitud">
                <input type="hidden" id="longitud" name="longitud">
                <input type="hidden" id="ciudad" name="ciudad">
                <input type="hidden" id="pais" name="pais">

                <p class="map-help-text">
                    Usa el campo de búsqueda o arrastra el marcador en el mapa para establecer tu ubicación.
                </p>

                <div class="form-group">
                    <label for="descripcion">Descripción:</label>
                    <textarea id="descripcion" name="descripcion"><?php echo htmlspecialchars($datosUsuario['descripcio']); ?></textarea>
                </div>
                
                <button type="submit" class="save-button" name="guardar_todo">Guardar Todos los Cambios</button>
            </form>
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
            <p id="footerText">© 2025 CardCapture. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="../js/script.js"></script>
    <script src="../js/footer_animation.js"></script>
    <script src="../js/scriptHeader.js"></script>
    <script src="../js/footerAnimation.js"></script>

    <script>
        let map;
        let marker;
        let autocomplete;
        let geocoder; // Define geocoder globally

        function initMap() {
            // Inicializa el geocoder
            geocoder = new google.maps.Geocoder();

            const localizacionInput = document.getElementById('localizacion');
            const initialLocationText = localizacionInput.value.trim();
            
            // Intenta geocodificar la ubicación actual del usuario para centrar el mapa
            if (initialLocationText) {
                geocoder.geocode({ 'address': initialLocationText }, function(results, status) {
                    let initialCoords = { lat: 41.5647, lng: 2.2599 }; // Coordenadas por defecto (Montornès del Vallès)
                    let initialZoom = 15;

                    if (status === 'OK' && results[0]) {
                        initialCoords = {
                            lat: results[0].geometry.location.lat(),
                            lng: results[0].geometry.location.lng()
                        };
                        initialZoom = 15; // Zoom más cercano para la ubicación encontrada
                        // Rellenar los campos ocultos al iniciar si se encontró una ubicación
                        document.getElementById('latitud').value = initialCoords.lat;
                        document.getElementById('longitud').value = initialCoords.lng;
                        // También actualiza ciudad y país si se encontró una ubicación válida
                        updateLocationFields(results[0].address_components);

                    } else {
                        console.log('Geocodificación inicial fallida para: ' + initialLocationText + ' - Status: ' + status);
                        // Si falla, se usan las coordenadas por defecto y se intenta actualizar los campos
                        document.getElementById('latitud').value = initialCoords.lat;
                        document.getElementById('longitud').value = initialCoords.lng;
                        // Intenta una geocodificación inversa para las coordenadas por defecto para obtener ciudad/país
                        geocodeLatLng(initialCoords, true);
                    }

                    // Crear el mapa
                    map = new google.maps.Map(document.getElementById('map'), {
                        center: initialCoords,
                        zoom: initialZoom
                    });

                    // Crear el marcador
                    marker = new google.maps.Marker({
                        map: map,
                        position: initialCoords,
                        draggable: true // Permitimos arrastrar el marcador
                    });

                    // Inicializar el servicio de autocompletado para el campo de texto de ubicación
                    autocomplete = new google.maps.places.Autocomplete(localizacionInput);
                    autocomplete.bindTo('bounds', map); // Restringe las sugerencias a la vista actual del mapa

                    // Escuchar el evento cuando el usuario selecciona un lugar del autocompletado
                    autocomplete.addListener('place_changed', function() {
                        marker.setVisible(false); // Ocultar el marcador mientras se procesa la nueva ubicación
                        const place = autocomplete.getPlace();

                        if (!place.geometry) {
                            // Si el lugar no tiene geometría (e.g., solo es una consulta de texto sin un lugar específico)
                            localizacionInput.classList.add('error'); // Opcional: añade una clase de error
                            console.log("No details available for input '" + place.name + "'");
                            return;
                        } else {
                            localizacionInput.classList.remove('error'); // Elimina la clase de error
                            // Mueve el mapa al nuevo lugar
                            map.setCenter(place.geometry.location);
                            map.setZoom(15); // Acercar el zoom al lugar seleccionado
                            marker.setPosition(place.geometry.location); // Mueve el marcador
                            marker.setVisible(true); // Hacer visible el marcador

                            // Llama a geocodeLatLng para rellenar los campos ocultos (latitud, longitud, ciudad, país)
                            geocodeLatLng(place.geometry.location, false);
                        }
                    });

                    // Geocodificación inversa al arrastrar el marcador
                    marker.addListener('dragend', function() {
                        geocodeLatLng(marker.getPosition(), false);
                    });
                });
            } else {
                // Si no hay ubicación inicial guardada, usa las coordenadas por defecto directamente
                const initialCoords = { lat: 41.5647, lng: 2.2599 }; // Montornès del Vallès
                
                map = new google.maps.Map(document.getElementById('map'), {
                    center: initialCoords,
                    zoom: 15
                });

                marker = new google.maps.Marker({
                    map: map,
                    position: initialCoords,
                    draggable: true
                });

                autocomplete = new google.maps.places.Autocomplete(localizacionInput);
                autocomplete.bindTo('bounds', map);
                autocomplete.addListener('place_changed', function() {
                    marker.setVisible(false);
                    const place = autocomplete.getPlace();
                    if (!place.geometry) {
                        localizacionInput.classList.add('error');
                        console.log("No details available for input '" + place.name + "'");
                        return;
                    } else {
                        localizacionInput.classList.remove('error');
                        map.setCenter(place.geometry.location);
                        map.setZoom(15);
                        marker.setPosition(place.geometry.location);
                        marker.setVisible(true);
                        geocodeLatLng(place.geometry.location, false);
                    }
                });
                marker.addListener('dragend', function() {
                    geocodeLatLng(marker.getPosition(), false);
                });
                // Rellena los campos ocultos con la ubicación por defecto
                geocodeLatLng(initialCoords, true);
            }
        }

        // Función para obtener la información de latitud, longitud, ciudad y país a partir de las coordenadas
        function geocodeLatLng(latlng, updateLocationInput) {
            geocoder.geocode({ location: latlng }, function(results, status) {
                if (status === 'OK') {
                    if (results[0]) {
                        // Actualiza el campo de texto de ubicación si se solicita (por ejemplo, al arrastrar el marcador o al iniciar con coordenadas por defecto)
                        if (updateLocationInput) {
                            document.getElementById('localizacion').value = results[0].formatted_address;
                        }

                        // Rellenar los campos ocultos de latitud y longitud
                        document.getElementById('latitud').value = latlng.lat();
                        document.getElementById('longitud').value = latlng.lng();

                        // Actualiza los campos de ciudad y país
                        updateLocationFields(results[0].address_components);

                        console.log('Latitud:', latlng.lat(), 'Longitud:', latlng.lng()); // Para depuración
                    } else {
                        console.log('No se encontraron resultados para la geocodificación inversa.');
                        // Si no se encuentran resultados, limpia los campos de ubicación
                        document.getElementById('latitud').value = '';
                        document.getElementById('longitud').value = '';
                        document.getElementById('ciudad').value = '';
                        document.getElementById('pais').value = '';
                        if (updateLocationInput) {
                            document.getElementById('localizacion').value = '';
                        }
                    }
                } else {
                    console.log('Fallo el geocodificador debido a: ' + status);
                    // Si hay un error, limpia los campos de latitud y longitud
                    document.getElementById('latitud').value = '';
                    document.getElementById('longitud').value = '';
                    document.getElementById('ciudad').value = '';
                    document.getElementById('pais').value = '';
                    if (updateLocationInput) {
                        document.getElementById('localizacion').value = '';
                    }
                }
            });
        }

        // Función auxiliar para extraer ciudad y país de los componentes de la dirección
        function updateLocationFields(addressComponents) {
            let city = '';
            let country = '';
            for (const component of addressComponents) {
                const types = component.types;
                if (types.includes('locality')) { // Ciudad
                    city = component.long_name;
                } else if (types.includes('country')) { // País
                    country = component.long_name;
                }
            }
            document.getElementById('ciudad').value = city;
            document.getElementById('pais').value = country;
            console.log('Ciudad:', city, 'País:', country); // Para depuración
        }


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

            // *** Lógica para previsualizar la imagen de perfil ***
            function previewImage(input) {
                if (input.files && input.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        document.querySelector('.profile-image').src = e.target.result;
                        document.querySelector('.profile-image').classList.add('image-changed');
                        setTimeout(function() {
                            document.querySelector('.profile-image').classList.remove('image-changed');
                        }, 500);
                    }
                    reader.readAsDataURL(input.files[0]);
                }
            }
            // Asigna la función previewImage al input de archivo
            document.getElementById('nueva_imagen').addEventListener('change', function() {
                previewImage(this);
            });
            
            // Nota: La lógica del mapa ahora está en initMap y se ejecuta con la API de Google Maps
            // La función initMap() se llama automáticamente una vez que la API de Google Maps se carga.
        });
    </script>
</body>
</html>