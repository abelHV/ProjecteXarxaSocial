<?php
session_start();
$sesionIniciada = isset($_SESSION['user_id']);

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
    <title>Subir Publicación - CardCapture</title>
    <link rel="shortcut icon" href="../img/logo.png" />
    <link rel="stylesheet" href="../css/INDEXmain.css">
    <link rel="stylesheet" href="../css/publicaciones.css">
    <style>
        /* Estilos específicos para la página de subida de publicación */
        .container-publicacion {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
        }

        .form-container {
            width: 90%;
            max-width: 700px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .form-container label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        .form-container input[type=text],
        .form-container input[type=number],
        .form-container select,
        .form-container textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .form-container textarea {
            resize: vertical;
            min-height: 100px;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 10px;
        }

        .file-input-wrapper input[type=file] {
            font-size: 100px;
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
        }

        /*
        El CSS para .image-preview-container y .image-preview
        ya está definido en publicaciones.css y es correcto.
        Asegúrate de que ese archivo CSS esté enlazado correctamente.
        */
        .image-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .image-preview {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
        }

        .form-container input[type=submit] {
            background-color: #28a745;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
        }

        .form-container input[type=submit]:hover {
            background-color: #218838;
        }

        .error-message {
            color: red;
            margin-top: 5px;
        }

        /* Estilos para la ventana modal de la imagen ampliada */
        .image-modal {
            display: none; /* Oculto por defecto */
            position: fixed; /* Cubre toda la ventana */
            z-index: 1; /* Estar por encima de todo */
            left: 0;
            top: 0;
            width: 100%; /* Ancho completo */
            height: 100%; /* Alto completo */
            overflow: auto; /* Habilitar scroll si la imagen es muy grande */
            background-color: rgba(0,0,0,0.9); /* Fondo oscuro y semitransparente */
        }

        .image-modal-content {
            margin: auto; /* Centrar la imagen */
            display: block;
            max-width: 90%; /* Ancho máximo de la imagen */
            max-height: 90%; /* Alto máximo de la imagen */
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        /* Botón para cerrar la ventana modal */
        .close-button {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            transition: 0.3s;
            cursor: pointer;
        }

        .close-button:hover,
        .close-button:focus {
            color: #bbb;
            text-decoration: none;
            cursor: pointer;
        }

        /* Estilos para el mapa de ubicación */
        #map {
            height: 300px;
            width: 100%;
            margin-bottom: 20px;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        input#ubicacion_texto.error {
            border-color: red;
            background-color: #ffe0e0;
        }
    </style>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDG95fZTPRuh52kKfnhZnyF79E1Kv9KwcM&libraries=places&callback=initMap"></script>
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
        <div class="container">
            <div class="left-side">
                <form action="../php/publicaciones.php" method="post" enctype="multipart/form-data">
                    <label for="titulo">Título:</label><br>
                    <input type="text" id="titulo" name="titulo" required><br><br>

                    <label for="descripcion">Descripción:</label><br>
                    <textarea id="descripcion" name="descripcion" required></textarea><br><br>

                    <label for="categoria">Categoría:</label><br>
                    <select id="categoria" name="categoria" required>
                        <option value="Magic">Magic</option>
                        <option value="Pokemon">Pokemon</option>
                        <option value="One Piece">One Piece</option>
                        <option value="Yu-Gi-Oh">Yu-Gi-Oh</option>
                        <option value="My Little Pony">My Little Pony</option>
                        <option value="Invizimals">Invizimals</option>
                    </select><br><br>

                    <label for="ubicacion_texto">Ubicación:</label><br>
                    <input type="text" id="ubicacion_texto" name="ubicacion_texto" placeholder="Buscar ubicación..." autocomplete="off"><br><br>

                    <div id="map"></div>

                    <input type="hidden" id="latitud" name="latitud">
                    <input type="hidden" id="longitud" name="longitud">

                    <input type="hidden" id="ciudad" name="ciudad">
                    <input type="hidden" id="pais" name="pais">

                    <label for="estado">Estado:</label><br>
                    <select id="estado" name="estado" required>
                        <option value="nuevo">Nuevo</option>
                        <option value="como_nuevo">Como Nuevo</option>
                        <option value="bueno">Bueno</option>
                        <option value="regular">Regular</option>
                        <option value="mal_estado">Mal Estado</option>
                    </select><br><br>

                    <label for="precio">Precio:</label><br>
                    <input type="number" id="precio" name="precio" step="0.01" min="0" required><br><br>

                    <label for="imagenes">Imágenes (máximo 12):</label><br>
                    <div class="file-input-wrapper">
                        <span>Seleccionar Imágenes</span>
                        <input type="file" id="imagenes" name="imagenes[]" accept="image/*" multiple>
                    </div>
                    <p id="imageLimitError" class="error-message"></p>
                    <br><br>

                    <input type="submit" value="Publicar">
                </form>
            </div>
            <div class="right-side">
                <h2>Vista Previa de Imágenes</h2>
                <div id="multipleImagePreview" class="image-preview-container">
                </div>
            </div>
        </div>

        <div id="imageModal" class="image-modal">
            <span class="close-button">&times;</span>
            <img class="image-modal-content" id="modalImage">
        </div>
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

    <script src="../js/script.js"></script>
    <script src="../js/footer_animation.js"></script>
    <script>
        let map;
        let marker;
        let autocomplete;

        function initMap() {
            // Coordenadas iniciales (por ejemplo, Barcelona, Montornès del Vallès)
            const initialCoords = { lat: 41.5647, lng: 2.2599 };

            map = new google.maps.Map(document.getElementById('map'), {
                center: initialCoords,
                zoom: 15 // Un zoom más cercano para empezar en Montornès del Vallès
            });

            marker = new google.maps.Marker({
                map: map,
                position: initialCoords, // Colocamos el marcador en el centro inicial
                draggable: true // Permitimos arrastrar el marcador
            });

            // Inicializar el servicio de autocompletado para el campo de texto de ubicación
            const locationInput = document.getElementById('ubicacion_texto');
            autocomplete = new google.maps.places.Autocomplete(locationInput);
            autocomplete.bindTo('bounds', map); // Restringe las sugerencias a la vista actual del mapa

            // Escuchar el evento cuando el usuario selecciona un lugar del autocompletado
            autocomplete.addListener('place_changed', function() {
                marker.setVisible(false); // Ocultar el marcador mientras se procesa la nueva ubicación
                const place = autocomplete.getPlace();

                if (!place.geometry) {
                    // Si el lugar no tiene geometría (e.g., solo es una consulta de texto sin un lugar específico)
                    locationInput.classList.add('error');
                    console.log("No details available for input '" + place.name + "'");
                    return;
                } else {
                    locationInput.classList.remove('error');
                    // Mueve el mapa al nuevo lugar
                    map.setCenter(place.geometry.location);
                    map.setZoom(15); // Acercar el zoom al lugar seleccionado
                    marker.setPosition(place.geometry.location); // Mueve el marcador
                    marker.setVisible(true); // Hacer visible el marcador

                    // Llama a geocodeLatLng para rellenar los campos ocultos (latitud, longitud, ciudad, país)
                    geocodeLatLng(place.geometry.location);
                }
            });

            // Geocodificación inversa al arrastrar el marcador
            marker.addListener('dragend', function() {
                geocodeLatLng(marker.getPosition());
            });

            // Geocodificación inicial al cargar el mapa para rellenar los campos ocultos
            // Esto es importante para que los campos tengan datos si el usuario no interactúa con el mapa
            geocodeLatLng(initialCoords);
        }

        // Función para obtener la información de latitud, longitud, ciudad y país a partir de las coordenadas
        function geocodeLatLng(latlng) {
            const geocoder = new google.maps.Geocoder();
            geocoder.geocode({ location: latlng }, function(results, status) {
                if (status === 'OK') {
                    if (results[0]) {
                        // Rellenar el campo de texto con la dirección formateada
                        document.getElementById('ubicacion_texto').value = results[0].formatted_address;

                        // Rellenar los campos ocultos de latitud y longitud
                        document.getElementById('latitud').value = latlng.lat();
                        document.getElementById('longitud').value = latlng.lng();

                        let city = '';
                        let country = '';
                        // Itera a través de los componentes de la dirección para encontrar la ciudad y el país
                        for (const component of results[0].address_components) {
                            const types = component.types;
                            if (types.includes('locality') || types.includes('administrative_area_level_2')) {
                                city = component.long_name;
                            } else if (types.includes('country')) {
                                country = component.long_name;
                            }
                        }
                        // Rellenar los campos ocultos de ciudad y país
                        document.getElementById('ciudad').value = city;
                        document.getElementById('pais').value = country;
                        console.log('Ciudad:', city, 'País:', country); // Para depuración
                        console.log('Latitud:', latlng.lat(), 'Longitud:', latlng.lng()); // Para depuración
                    } else {
                        console.log('No se encontraron resultados para la geocodificación inversa.');
                    }
                } else {
                    console.log('Fallo el geocodificador debido a: ' + status);
                    // Si hay un error, limpia los campos de latitud y longitud
                    document.getElementById('latitud').value = '';
                    document.getElementById('longitud').value = '';
                    document.getElementById('ciudad').value = '';
                    document.getElementById('pais').value = '';
                }
            });
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

            // *** Lógica para la previsualización y modal de imágenes ***
            const maxImages = 12;
            const imageInput = document.getElementById('imagenes');
            // CAMBIO CLAVE: Ahora apunta a 'multipleImagePreview' en el right-side
            const previewContainer = document.getElementById('multipleImagePreview');
            const imageLimitError = document.getElementById('imageLimitError');
            const imageModal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            const closeButton = document.querySelector('.close-button');

            imageInput.addEventListener('change', function(event) {
                const files = event.target.files;
                previewContainer.innerHTML = ''; // Limpiar previsualizaciones anteriores
                imageLimitError.textContent = '';

                if (files.length > maxImages) {
                    imageLimitError.textContent = `Solo se pueden seleccionar un máximo de ${maxImages} imágenes.`;
                    // Opcional: limpiar la selección de archivos si excede el límite
                    imageInput.value = '';
                    return;
                }

                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.classList.add('image-preview');
                            img.addEventListener('click', function() {
                                modalImage.src = this.src;
                                imageModal.style.display = "block";
                            });
                            previewContainer.appendChild(img);
                        }
                        reader.readAsDataURL(file);
                    }
                }
            });

            // Cerrar la ventana modal al hacer clic en la 'x'
            closeButton.addEventListener('click', function() {
                imageModal.style.display = "none";
            });

            // Cerrar la ventana modal al hacer clic fuera de la imagen
            window.addEventListener('click', function(event) {
                if (event.target == imageModal) {
                    imageModal.style.display = "none";
                }
            });

            // Lógica para el sticky de la vista previa de imágenes (right-side)
            const rightSide = document.querySelector('.right-side');
            const scrollThreshold = 200; // Ajusta este valor según necesites

            // Aplica la forma inicial al cargar la página
            rightSide.classList.add('initial-shape');

            function updateRightSidePosition() {
                // Se fija si el scroll supera el umbral o si el menú de categorías ya está fijo
                if (window.scrollY > scrollThreshold || menuCategorias.classList.contains('sticky')) {
                    rightSide.classList.remove('initial-shape');
                    rightSide.classList.add('fixed');
                } else {
                    rightSide.classList.remove('fixed');
                    rightSide.classList.add('initial-shape');
                }
            }

            window.addEventListener('scroll', updateRightSidePosition);

            // También verifica la posición al cargar por si el usuario ya está scrolleado
            updateRightSidePosition();
        });
    </script>
    <script src="../js/scriptHeader.js"></script>
    <script src="../js/footerAnimation.js"></script>
</body>
</html>