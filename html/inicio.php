<?php
session_start();
$sesionIniciada = isset($_SESSION['user_id']);

// Incluir el archivo de conexión a la base de datos
include '../php/db.php'; // Asegúrate de que $conn está inicializado

// --- Nuevas constantes para la escala del slider ---
define('SLIDER_SCALE_MIN', 0);
define('SLIDER_SCALE_MAX', 3000); // Precisión de la escala interna del slider
define('SLIDER_EXPONENT', 2.0);   // Exponente para la curva (2.0 para cuadrática, >1 para acelerar al final)
define('PRICE_MIN_GAP', 1);       // Diferencia mínima de precio en euros

// --- Función helper en PHP para mapear precio a valor de slider ---
function phpMapPriceToSlider($price, $actualMin, $actualMax, $sliderMin, $sliderMax, $exp) {
    if ($exp == 0) return $sliderMin;
    if ($actualMax <= $actualMin) { // Si el rango de precios es inválido o cero
        return ($price <= $actualMin) ? $sliderMin : $sliderMax;
    }
    $price = max($actualMin, min($actualMax, $price)); // Asegurar que el precio está dentro de los límites reales
    $normalizedPrice = ($price - $actualMin) / ($actualMax - $actualMin);
    if ($normalizedPrice < 0) $normalizedPrice = 0; // Por si acaso con floats
    if ($normalizedPrice > 1) $normalizedPrice = 1;

    $powerVal = pow($normalizedPrice, 1 / $exp);
    return round($sliderMin + ($sliderMax - $sliderMin) * $powerVal);
}

// Obtener la categoría de la URL y sanitizarla
$categoria = isset($_GET['categoria']) ? $conn->real_escape_string($_GET['categoria']) : '';

// Consulta SQL base
$sql = "SELECT p.* FROM publicacions p WHERE p.categoria = '$categoria'";

// Si hay una sesión iniciada, filtrar los anuncios del usuario actual
if ($sesionIniciada) {
    $usuario_id = $_SESSION['user_id'];
    $sql .= " AND p.usuario_id != " . intval($usuario_id);
}

// Variables para los filtros
$orderBy = isset($_GET['orden']) ? $_GET['orden'] : 'fecha_actualizacion_desc';

// Obtener el rango de precios máximo real de la base de datos
$sqlMaxPrecio = "SELECT MAX(precio) AS max_precio FROM publicacions WHERE categoria = '$categoria'";
$resultMaxPrecio = $conn->query($sqlMaxPrecio);
$maxPrecioDB = 0;
if ($resultMaxPrecio && $resultMaxPrecio->num_rows > 0) {
    $rowMaxPrecio = $resultMaxPrecio->fetch_assoc();
    $maxPrecioDB = $rowMaxPrecio['max_precio'] ?? 20000;
} else {
    $maxPrecioDB = 20000; // Valor por defecto si no hay anuncios o error
}
$maxSliderActual = max(20000, floatval($maxPrecioDB)); // Precio máximo REAL que se usará en los sliders y filtros

// Obtener precioMin y precioMax de GET, con defaults
$precioMin = isset($_GET['precio_min']) ? floatval($_GET['precio_min']) : 0;
$precioMax = isset($_GET['precio_max']) ? floatval($_GET['precio_max']) : $maxSliderActual;

// Validar y ajustar precioMin y precioMax
$precioMin = max(0, min($precioMin, $maxSliderActual));
$precioMax = max(0, min($precioMax, $maxSliderActual));
if ($precioMin > $precioMax) {
    $precioMin = $precioMax; // O $temp = $precioMin; $precioMin = $precioMax; $precioMax = $temp;
}
// Aplicar PRICE_MIN_GAP en PHP para los valores iniciales
if ($precioMax - $precioMin < PRICE_MIN_GAP && !($precioMin == 0 && $precioMax == 0) ) {
    if ($precioMax == $maxSliderActual) { // Si max está al tope, ajustar min
        $precioMin = max(0, $precioMax - PRICE_MIN_GAP);
    } else { // Ajustar max
        $precioMax = min($maxSliderActual, $precioMin + PRICE_MIN_GAP);
    }
}


$fechaFiltro = isset($_GET['fecha_filtro']) ? $_GET['fecha_filtro'] : '';
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Función para añadir filtros de fecha a la consulta SQL
function aplicarFiltroFecha($sql_query, $fecha_filtro_val) {
    $hoy = date("Y-m-d H:i:s");
    if ($fecha_filtro_val === 'dia') {
        $ayer = date("Y-m-d 00:00:00", strtotime("-1 day"));
        $sql_query .= " AND p.fecha_actualizacion >= '$ayer' AND p.fecha_actualizacion <= '$hoy'";
    } elseif ($fecha_filtro_val === 'semana') {
        $semanaPasada = date("Y-m-d 00:00:00", strtotime("-7 days"));
        $sql_query .= " AND p.fecha_actualizacion >= '$semanaPasada' AND p.fecha_actualizacion <= '$hoy'";
    } elseif ($fecha_filtro_val === 'mes') {
        $mesPasado = date("Y-m-d 00:00:00", strtotime("-1 month"));
        $sql_query .= " AND p.fecha_actualizacion >= '$mesPasado' AND p.fecha_actualizacion <= '$hoy'";
    }
    return $sql_query;
}

// Aplicar filtro de búsqueda por nombre
if (!empty($searchTerm)) {
    $escapedSearchTerm = $conn->real_escape_string($searchTerm);
    $sql .= " AND p.titulo LIKE '%$escapedSearchTerm%'";
}

// Aplicar filtros de fecha a la consulta SQL
$sql = aplicarFiltroFecha($sql, $fechaFiltro);

// Aplicar filtros de precio a la consulta SQL (usando $precioMin y $precioMax ya validados)
$sql .= " AND p.precio >= " . floatval($precioMin);
$sql .= " AND p.precio <= " . floatval($precioMax);


// Aplicar el ordenamiento DESPUÉS de todos los filtros WHERE
if ($orderBy === 'precio_asc') {
    $sql .= " ORDER BY p.precio ASC";
} elseif ($orderBy === 'precio_desc') {
    $sql .= " ORDER BY p.precio DESC";
} elseif ($orderBy === 'fecha_actualizacion_desc') {
    $sql .= " ORDER BY p.fecha_actualizacion DESC";
}

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CardCapture - Anuncios de <?php echo htmlspecialchars($categoria); ?></title>
    <link rel="shortcut icon" href="../img/logo.png" />
    <link rel="stylesheet" href="../css/css.css">
    <link rel="stylesheet" href="../css/PAGINIcssHeaderFooter.css">
</head>
<body>
    <header class="headerx">
        <div class="logo" id="logoInicio">CARDCAPTURE</div>
        <div class="user-menu">
            <div class="iconx" id="userIcon">
                <img src="../img/user.png" class="user-icon" alt="">
            </div>
            <ul class="dropdown" id="dropdownMenu">
                <?php if (!$sesionIniciada): ?>
                    <li><a href="../html/InicioSesion.php">Iniciar Sesión</a></li>
                <?php else: ?>
                    <li><a href="../html/perfil.php">Perfil</a></li>
                    <li><a href="../html/meGusta.php">Favoritos</a></li>
                    <li><a href="../html/publicaciones.php">Vender</a></li>
                    <li><a href="../html/chat.php">Buzón</a></li>
                    <li><a href="../php/logout.php">Cerrar Sesión</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </header>
    <div class="divSearch">
        <input type="text" class="search" id="searchInput" placeholder="Buscar anuncios..." value="<?php echo htmlspecialchars($searchTerm); ?>">
        <ul id="suggestions"></ul>
        <input type="hidden" id="hiddenSearchInput" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>">
    </div>

    <script src="../js/scriptHeader.js"></script>
    <div class="filter-container">
        <button class="filter-toggle-btn">Mostrar Filtros</button>
    </div>
    <div class="filter-tab">
        <button class="filter-tab-btn">
            <img src="../img/filtro.png" alt="">
        </button>
    </div>
    <div class="filter-panel">
        <h3>Filtrar Anuncios</h3>
        <form action="" method="GET">
            <input type="hidden" name="categoria" value="<?php echo htmlspecialchars($categoria); ?>">
            <input type="hidden" name="search" id="formSearchInput" value="<?php echo htmlspecialchars($searchTerm); ?>">

            <div class="filter-group">
                <label for="fecha_filtro">Fecha de Actualización:</label>
                <select name="fecha_filtro" id="fecha_filtro">
                    <option value="" <?php if ($fechaFiltro === '') echo 'selected'; ?>>Sin filtro</option>
                    <option value="dia" <?php if ($fechaFiltro === 'dia') echo 'selected'; ?>>Último día</option>
                    <option value="semana" <?php if ($fechaFiltro === 'semana') echo 'selected'; ?>>Última semana</option>
                    <option value="mes" <?php if ($fechaFiltro === 'mes') echo 'selected'; ?>>Último mes</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="orden">Ordenar por Precio:</label>
                <select name="orden" id="orden">
                    <option value="fecha_actualizacion_desc" <?php if ($orderBy === 'fecha_actualizacion_desc') echo 'selected'; ?>>Sin ordenar por precio</option>
                    <option value="precio_desc" <?php if ($orderBy === 'precio_desc') echo 'selected'; ?>>Más caro primero</option>
                    <option value="precio_asc" <?php if ($orderBy === 'precio_asc') echo 'selected'; ?>>Más barato primero</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="price-range">Rango de Precio (0 - <?php echo number_format($maxSliderActual, 0, ',', '.'); ?> €):</label>
                <div class="range-slider-container">
                    <div class="range-slider"></div>
                    <div class="range-selected"></div>
                    <div class="range-input">
                        <input type="range" 
                               min="<?php echo SLIDER_SCALE_MIN; ?>" 
                               max="<?php echo SLIDER_SCALE_MAX; ?>" 
                               value="<?php echo phpMapPriceToSlider($precioMin, 0, $maxSliderActual, SLIDER_SCALE_MIN, SLIDER_SCALE_MAX, SLIDER_EXPONENT); ?>" 
                               id="min-price-slider">
                        <input type="range" 
                               min="<?php echo SLIDER_SCALE_MIN; ?>" 
                               max="<?php echo SLIDER_SCALE_MAX; ?>" 
                               value="<?php echo phpMapPriceToSlider($precioMax, 0, $maxSliderActual, SLIDER_SCALE_MIN, SLIDER_SCALE_MAX, SLIDER_EXPONENT); ?>" 
                               id="max-price-slider">
                    </div>
                </div>
                <div class="price-inputs">
                    <input type="number" id="price-min-input" value="<?php echo floatval($precioMin); ?>" min="0" max="<?php echo floatval($maxSliderActual); ?>">
                    <input type="number" id="price-max-input" value="<?php echo floatval($precioMax); ?>" min="0" max="<?php echo floatval($maxSliderActual); ?>">
                </div>
                <input type="hidden" name="precio_min" id="precio_min_hidden" value="<?php echo floatval($precioMin); ?>">
                <input type="hidden" name="precio_max" id="precio_max_hidden" value="<?php echo floatval($precioMax); ?>">
            </div>

            <button type="submit">Aplicar Filtros</button>
        </form>
    </div>

    <main>
   <section class="anuncios">
    <?php
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql_primera_imagen = "SELECT imagen FROM galeria_fotos WHERE publicacion_id = " . intval($row['publicacion_id']) . " LIMIT 1";
            $result_primera_imagen = $conn->query($sql_primera_imagen);
            $ruta_primera_imagen = '';
            if ($result_primera_imagen && $result_primera_imagen->num_rows > 0) {
                $ruta_primera_imagen = $result_primera_imagen->fetch_assoc()['imagen'];
            }

            echo "<div class='anuncio'>";
            echo "<a href='detalle_publicacion.php?id=" . intval($row['publicacion_id']) . "' class='anuncio-link'>";
            echo "<div class='anuncio-imagen-container'>"; // INICIA contenedor de imagen

            if ($ruta_primera_imagen) {
                echo "<img src='" . htmlspecialchars($ruta_primera_imagen) . "' alt='Imagen del anuncio'>";
            } else {
                echo "<img src='../img/placeholder.png' alt='Sin imagen'>";
            }

            // AHORA EL BOTÓN DE ME GUSTA VA AQUÍ, DENTRO DEL CONTENEDOR DE LA IMAGEN
            if (isset($_SESSION['user_id'])) {
                // Tendrías que consultar si el usuario ya le dio like a esta publicación
                // Por ahora, solo muestra el botón
                $isLiked = false; // Asume falso por defecto, luego consulta DB si es necesario
                // Ejemplo: $sql_is_liked = "SELECT COUNT(*) FROM likes WHERE user_id = " . intval($_SESSION['user_id']) . " AND publicacion_id = " . intval($row['publicacion_id']);
                // $result_is_liked = $conn->query($sql_is_liked);
                // if ($result_is_liked && $result_is_liked->fetch_row()[0] > 0) { $isLiked = true; }

                echo "<button class='like-button " . ($isLiked ? 'liked' : '') . "' data-publicacion-id='" . intval($row['publicacion_id']) . "'>";
                // SVG del corazón
                echo "<svg class='heart-icon' viewBox='0 0 32 29.6'><path d='M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2c6.1-9.3,16-11.8,16-21.2C32,3.8,28.2,0,23.6,0z'/></svg>";
                echo "</button>";
            }

            echo "</div>"; // CIERRA contenedor de imagen

            echo "<div class='anuncio-info'>";
            echo "<h3>" . htmlspecialchars($row['titulo']) . "</h3>";
            echo "<p class='precio'>" . number_format(floatval($row['precio']), 0, ',', '.') . " €</p>";
            echo "</div>"; // Cierra anuncio-info

            echo "<p class='fecha-publicacion'>Publicado el: " . (isset($row['fecha_actualizacion']) && $row['fecha_actualizacion'] != '0000-00-00 00:00:00' ? date('d/m/Y', strtotime($row['fecha_actualizacion'])) : 'Fecha no disponible') . "</p>";

            echo "</a>"; // Cierra anuncio-link
            echo "</div>"; // Cierra anuncio
        }
    } else {
        echo "<p class='no-anuncios'>No hay anuncios disponibles con los filtros seleccionados.</p>";
    }
    ?>
</section>
    </main>
    <footer id="footer" class="footer">
        <div class="footer-container">
            <div class="footer-logo">
                <h2 id="footerTitle">CardCapture</h2>
                <p>Explora, compra y vende cartas de colección fácilmente.</p>
            </div>
            <div class="footer-social">
                <h3>Síguenos</h3>
                <div class="social-icons">
                    <a href="https://www.facebook.com/" target="_blank"><img class="icon" src="../img/facebook.png" alt="Facebook"></a>
                    <a href="https://x.com/home?lang=es" target="_blank"><img class="icon" src="../img/twitter.png" alt="Twitter"></a>
                    <a href="https://www.instagram.com/" target="_blank"><img class="icon" src="../img/instagram.png" alt="Instagram"></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p id="footerText">&copy; <?php echo date("Y"); ?> CardCapture. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const filterTabBtn = document.querySelector('.filter-tab-btn');
        const filterPanel = document.querySelector('.filter-panel');
        const anunciosSection = document.querySelector('.anuncios');
        const filterTab = document.querySelector('.filter-tab');
        
        const rangeInput = document.querySelectorAll('.range-input input');
        const priceInput = document.querySelectorAll('.price-inputs input');
        const rangeSelected = document.querySelector('.range-selected');
        const precioMinHidden = document.querySelector('#precio_min_hidden');
        const precioMaxHidden = document.querySelector('#precio_max_hidden');
        
        const likeButtons = document.querySelectorAll('.like-button');
        const searchInput = document.getElementById('searchInput');
        const suggestionsList = document.getElementById('suggestions');
        const hiddenSearchInput = document.getElementById('hiddenSearchInput');
        const formSearchInput = document.getElementById('formSearchInput');
        const logoInicio = document.getElementById('logoInicio');
        const header = document.querySelector('.headerx'); 
        const searchMenu = document.querySelector('.divSearch'); 

        let searchTimeout;
        let isFilterOpen = false;

        // --- Constantes para la escala no lineal del slider de precios ---
        const ACTUAL_MIN_PRICE = 0;
        const ACTUAL_MAX_PRICE = parseFloat(<?php echo json_encode($maxSliderActual); ?>);
        const SLIDER_SCALE_MIN = parseInt(<?php echo json_encode(SLIDER_SCALE_MIN); ?>);
        const SLIDER_SCALE_MAX = parseInt(<?php echo json_encode(SLIDER_SCALE_MAX); ?>);
        const SLIDER_EXPONENT = parseFloat(<?php echo json_encode(SLIDER_EXPONENT); ?>);
        const PRICE_GAP = parseInt(<?php echo json_encode(PRICE_MIN_GAP); ?>); // Diferencia mínima en euros

        // --- Funciones de mapeo para el slider de precios ---
        function mapSliderToPrice(sliderValue) {
            if (ACTUAL_MAX_PRICE <= ACTUAL_MIN_PRICE) return ACTUAL_MIN_PRICE;
            sliderValue = Math.max(SLIDER_SCALE_MIN, Math.min(SLIDER_SCALE_MAX, sliderValue)); // Clamp sliderValue

            if (sliderValue === SLIDER_SCALE_MIN) return ACTUAL_MIN_PRICE;
            if (sliderValue === SLIDER_SCALE_MAX) return ACTUAL_MAX_PRICE;

            const normalizedSliderValue = (sliderValue - SLIDER_SCALE_MIN) / (SLIDER_SCALE_MAX - SLIDER_SCALE_MIN);
            let price = ACTUAL_MIN_PRICE + (ACTUAL_MAX_PRICE - ACTUAL_MIN_PRICE) * Math.pow(normalizedSliderValue, SLIDER_EXPONENT);
            return Math.round(price);
        }

        function mapPriceToSlider(priceValue) {
            if (ACTUAL_MAX_PRICE <= ACTUAL_MIN_PRICE) {
                 return (priceValue <= ACTUAL_MIN_PRICE) ? SLIDER_SCALE_MIN : SLIDER_SCALE_MAX;
            }
            priceValue = Math.max(ACTUAL_MIN_PRICE, Math.min(ACTUAL_MAX_PRICE, priceValue)); // Clamp priceValue

            if (priceValue === ACTUAL_MIN_PRICE) return SLIDER_SCALE_MIN;
            if (priceValue === ACTUAL_MAX_PRICE) return SLIDER_SCALE_MAX;
            
            const normalizedPriceValue = (priceValue - ACTUAL_MIN_PRICE) / (ACTUAL_MAX_PRICE - ACTUAL_MIN_PRICE);
            const sliderValue = SLIDER_SCALE_MIN + (SLIDER_SCALE_MAX - SLIDER_SCALE_MIN) * Math.pow(normalizedPriceValue, 1 / SLIDER_EXPONENT);
            return Math.round(sliderValue);
        }

        function updatePriceSliderUI(sourceEvent) {
            if (!rangeInput[0] || !rangeInput[1] || !priceInput[0] || !priceInput[1] || !precioMinHidden || !precioMaxHidden || !rangeSelected) return;

            let minSliderVal = parseInt(rangeInput[0].value);
            let maxSliderVal = parseInt(rangeInput[1].value);
            
            // 1. Sincronizar valores de slider (asegurar min <= max)
            if (minSliderVal > maxSliderVal) {
                if (sourceEvent && sourceEvent.target.id === "min-price-slider") {
                    maxSliderVal = minSliderVal;
                } else {
                    minSliderVal = maxSliderVal;
                }
            }
            // Clampear valores del slider a su rango definido
            minSliderVal = Math.max(SLIDER_SCALE_MIN, Math.min(minSliderVal, SLIDER_SCALE_MAX));
            maxSliderVal = Math.max(SLIDER_SCALE_MIN, Math.min(maxSliderVal, SLIDER_SCALE_MAX));
            if (minSliderVal > maxSliderVal) { // Re-check post clamp
                 if (sourceEvent && sourceEvent.target.id === "min-price-slider")  maxSliderVal = minSliderVal; else minSliderVal = maxSliderVal;
            }


            // 2. Convertir a precios reales
            let actualMinPrice = mapSliderToPrice(minSliderVal);
            let actualMaxPrice = mapSliderToPrice(maxSliderVal);

            // 3. Aplicar PRICE_GAP a los precios reales
            //    (Solo si el rango total es mayor que el gap)
            if (ACTUAL_MAX_PRICE - ACTUAL_MIN_PRICE >= PRICE_GAP) {
                if (actualMaxPrice - actualMinPrice < PRICE_GAP) {
                    if (sourceEvent && sourceEvent.target.id === "min-price-slider") { // Min slider se movió
                        actualMinPrice = actualMaxPrice - PRICE_GAP;
                        if (actualMinPrice < ACTUAL_MIN_PRICE) {
                            actualMinPrice = ACTUAL_MIN_PRICE;
                            actualMaxPrice = Math.min(ACTUAL_MAX_PRICE, actualMinPrice + PRICE_GAP);
                        }
                    } else { // Max slider se movió o fue un input de texto/programático
                        actualMaxPrice = actualMinPrice + PRICE_GAP;
                        if (actualMaxPrice > ACTUAL_MAX_PRICE) {
                            actualMaxPrice = ACTUAL_MAX_PRICE;
                            actualMinPrice = Math.max(ACTUAL_MIN_PRICE, actualMaxPrice - PRICE_GAP);
                        }
                    }
                }
            } else { // El rango total es menor que el PRICE_GAP, así que los precios deben ser los extremos
                actualMinPrice = ACTUAL_MIN_PRICE;
                actualMaxPrice = ACTUAL_MAX_PRICE;
            }
            
            // Re-clampear precios por si acaso
            actualMinPrice = Math.max(ACTUAL_MIN_PRICE, Math.min(actualMinPrice, ACTUAL_MAX_PRICE));
            actualMaxPrice = Math.max(actualMinPrice, Math.min(actualMaxPrice, ACTUAL_MAX_PRICE)); // Asegurar max >= min


            // 4. Actualizar los inputs de texto y los campos ocultos con los precios finales
            priceInput[0].value = actualMinPrice;
            priceInput[1].value = actualMaxPrice;
            precioMinHidden.value = actualMinPrice;
            precioMaxHidden.value = actualMaxPrice;

            // 5. Actualizar los sliders con los valores mapeados desde los precios finales (para sincronización)
            rangeInput[0].value = mapPriceToSlider(actualMinPrice);
            rangeInput[1].value = mapPriceToSlider(actualMaxPrice);

            // 6. Actualizar la barra de progreso visual usando los valores finales del slider
            const finalMinSliderForBar = parseInt(rangeInput[0].value);
            const finalMaxSliderForBar = parseInt(rangeInput[1].value);

            const progressStart = ((finalMinSliderForBar - SLIDER_SCALE_MIN) / (SLIDER_SCALE_MAX - SLIDER_SCALE_MIN)) * 100;
            const progressEnd = ((finalMaxSliderForBar - SLIDER_SCALE_MIN) / (SLIDER_SCALE_MAX - SLIDER_SCALE_MIN)) * 100;
            rangeSelected.style.left = Math.max(0, Math.min(100, progressStart)) + "%";
            rangeSelected.style.right = Math.max(0, Math.min(100, (100 - progressEnd))) + "%";
        }

        rangeInput.forEach(input => {
            input.addEventListener('input', function(event) {
                updatePriceSliderUI(event);
            });
        });

        priceInput.forEach(input => {
            input.addEventListener('input', function(event) {
                let minPrice = parseInt(priceInput[0].value);
                let maxPrice = parseInt(priceInput[1].value);

                if (isNaN(minPrice)) minPrice = ACTUAL_MIN_PRICE;
                if (isNaN(maxPrice)) maxPrice = ACTUAL_MAX_PRICE;
                
                // Validar y clampear precios ingresados
                minPrice = Math.max(ACTUAL_MIN_PRICE, Math.min(minPrice, ACTUAL_MAX_PRICE));
                maxPrice = Math.max(ACTUAL_MIN_PRICE, Math.min(maxPrice, ACTUAL_MAX_PRICE));
                
                if (minPrice > maxPrice) {
                    if (event.target.id === "price-min-input") maxPrice = minPrice;
                    else minPrice = maxPrice;
                }
                
                // Asignar a los sliders y llamar a updatePriceSliderUI para recalcular y sincronizar todo
                rangeInput[0].value = mapPriceToSlider(minPrice);
                rangeInput[1].value = mapPriceToSlider(maxPrice);
                updatePriceSliderUI(event); // El evento aquí ayuda a la lógica del PRICE_GAP
            });
        });
        
        // Inicializar la UI del slider de precios al cargar la página
        updatePriceSliderUI();


        // --- Resto de tu JavaScript (logo, filtros, likes, búsqueda, etc.) ---
        if (logoInicio) {
            logoInicio.addEventListener('click', function() { window.location.href = '../index.php'; });
            logoInicio.style.cursor = 'pointer';
        }

        if (filterTabBtn && filterPanel && anunciosSection && filterTab) {
            filterTabBtn.addEventListener('click', function() {
                isFilterOpen = !isFilterOpen;
                filterPanel.classList.toggle('open');
                filterTab.classList.toggle('open');
                anunciosSection.classList.toggle('open-filter', isFilterOpen);
            });
        }
        
        likeButtons.forEach(button => {
            button.addEventListener('click', function(event) {
                event.preventDefault(); 
                this.classList.toggle('liked');
                const publicacionId = this.dataset.publicacionId;
                const isLiked = this.classList.contains('liked');
                fetch('../php/favoritos.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `publicacion_id=${publicacionId}&accion=${isLiked ? 'agregar' : 'eliminar'}`
                })
                .then(response => response.text())
                .then(data => { console.log(data); })
                .catch(error => { console.error('Error al actualizar favoritos:', error); });
            });
        });

        function cargarEstadoFavoritos() {
            fetch('../php/favoritos.php?accion=obtener_favoritos')
            .then(response => response.json())
            .then(favoritos => {
                likeButtons.forEach(button => {
                    const publicacionId = button.dataset.publicacionId;
                    if (favoritos.includes(publicacionId)) button.classList.add('liked');
                });
            })
            .catch(error => { console.error('Error al cargar favoritos:', error); });
        }
        <?php if ($sesionIniciada): ?>
        cargarEstadoFavoritos();
        <?php endif; ?>

        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            if(hiddenSearchInput) hiddenSearchInput.value = query;
            if(formSearchInput) formSearchInput.value = query;
            clearTimeout(searchTimeout);
            if (query.length >= 2) {
                searchTimeout = setTimeout(() => fetchSuggestions(query), 200); 
            } else {
                if(suggestionsList) suggestionsList.innerHTML = '';
            }
        });

        function fetchSuggestions(query) {
            if(!suggestionsList) return;
            fetch(`../php/buscar_sugerencias.php?categoria=<?php echo urlencode($categoria); ?>&term_inicio=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                suggestionsList.innerHTML = '';
                if (data.length > 0) {
                    data.forEach(suggestion => {
                        const li = document.createElement('li');
                        li.textContent = suggestion;
                        li.addEventListener('click', function() {
                            searchInput.value = suggestion;
                            if(hiddenSearchInput) hiddenSearchInput.value = suggestion;
                            if(formSearchInput) formSearchInput.value = suggestion;
                            suggestionsList.innerHTML = '';
                            const form = document.querySelector('.filter-panel form');
                            if (form) form.submit();
                            else window.location.href = `?categoria=<?php echo urlencode($categoria); ?>&search=${encodeURIComponent(suggestion)}`;
                        });
                        suggestionsList.appendChild(li);
                    });
                }
            })
            .catch(error => { console.error('Error al obtener sugerencias:', error); });
        }

        const initialSearchTerm = <?php echo json_encode($searchTerm); ?>;
        if (initialSearchTerm) {
            searchInput.value = initialSearchTerm;
            if(hiddenSearchInput) hiddenSearchInput.value = initialSearchTerm;
            if(formSearchInput) formSearchInput.value = initialSearchTerm;
        }

        searchInput.addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                const form = document.querySelector('.filter-panel form');
                if(formSearchInput) formSearchInput.value = this.value.trim();
                if (form) form.submit();
                else window.location.href = `?categoria=<?php echo urlencode($categoria); ?>&search=${encodeURIComponent(this.value.trim())}`;
            }
        });

        window.addEventListener('scroll', () => {
            if (!header || !searchMenu) return;
            if (window.scrollY > header.offsetHeight && !searchMenu.classList.contains('sticky')) {
                searchMenu.classList.add('sticky');
            } else if (window.scrollY <= header.offsetHeight && searchMenu.classList.contains('sticky')) {
                searchMenu.classList.remove('sticky');
            }
        });
    });
    </script>
</body>
</html>