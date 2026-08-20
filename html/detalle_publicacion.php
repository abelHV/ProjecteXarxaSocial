<?php
session_start();
$sesionIniciada = isset($_SESSION['user_id']);

// Incluir el archivo de conexión a la base de datos
include '../php/db.php';

// Función para registrar la visita del usuario
function registrarVisita($publicacion_id, $usuario_id, $conn) {
    // Generar un identificador único para esta visita
    $visita_id = session_id() . '_' . $publicacion_id;

    // Verificar si el usuario ya ha visitado esta publicación antes
    $sql_verificar_visita = "SELECT 1 FROM visitas_publicacion WHERE visita_id = '$visita_id'";
    $result_verificar_visita = $conn->query($sql_verificar_visita);

    if ($result_verificar_visita->num_rows == 0) {
        // Si no ha visitado, incrementar el contador de visualizaciones
        $sql_incrementar_visualizaciones = "UPDATE publicacions SET visualizacion = visualizacion + 1 WHERE publicacion_id = $publicacion_id";
        if ($conn->query($sql_incrementar_visualizaciones)) {
            // Registrar la visita para evitar contarla nuevamente
            $sql_registrar_visita = "INSERT INTO visitas_publicacion (visita_id, publicacion_id, usuario_id) VALUES ('$visita_id', $publicacion_id, $usuario_id)";
            $conn->query($sql_registrar_visita);
        }
    }
}

// Verificar si el ID de la publicación está definido
if (isset($_GET['id'])) {
    $publicacion_id = $_GET['id'];

    // Si el usuario ha iniciado sesión, registrar la visita
    if ($sesionIniciada) {
        $usuario_id = $_SESSION['user_id'];
        registrarVisita($publicacion_id, $usuario_id, $conn);
    }
} else {
    echo "Error: ID de publicación no especificado.";
    exit;
}

// Consulta SQL corregida para usar id_user y obtener id_publicacion
$sql = "SELECT p.*, u.username AS nombre_vendedor, u.id_user AS vendedor_id
        FROM publicacions p
        JOIN usuari u ON p.usuario_id = u.id_user
        WHERE p.publicacion_id = $publicacion_id";

$result = $conn->query($sql);

if (!$result) {
    echo "Error en la consulta: " . $conn->error . "<br>";
    exit;
}

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $vendedor_id = $row['vendedor_id'];

    // Obtener las imágenes de la galería desde la tabla galeria_fotos
    $sql_galeria = "SELECT imagen FROM galeria_fotos WHERE publicacion_id = $publicacion_id";
    $result_galeria = $conn->query($sql_galeria);

    if (!$result_galeria) {
        echo "Error al obtener imágenes de la galería: " . $conn->error . "<br>";
        exit;
    }

    $imagenes_galeria = array();
    while ($row_galeria = $result_galeria->fetch_assoc()) {
        $imagenes_galeria[] = $row_galeria['imagen'];
    }

} else {
    echo "Publicación no encontrada.";
    exit;
}

// Crear un nuevo chat si se hace clic en el botón de chat
if (isset($_GET['crear_chat'])) {
    $usuario_id = $_SESSION['user_id'];

    $sql_verificar_chat = "SELECT chat_id FROM chats 
                                                WHERE (usuario1_id = $usuario_id AND usuario2_id = $vendedor_id AND id_publicacion = $publicacion_id) 
                                                OR (usuario1_id = $vendedor_id AND usuario2_id = $usuario_id AND id_publicacion = $publicacion_id)";
    $result_verificar_chat = $conn->query($sql_verificar_chat);

    if ($result_verificar_chat->num_rows > 0) {
        $row_chat = $result_verificar_chat->fetch_assoc();
        $chat_id = $row_chat['chat_id'];
        header("Location: chat.php?chat_id=$chat_id");
        exit;
    } else {
        $sql_crear_chat = "INSERT INTO chats (usuario1_id, usuario2_id, id_publicacion) 
                                                                 VALUES ($usuario_id, $vendedor_id, $publicacion_id)";
        if ($conn->query($sql_crear_chat)) {
            $chat_id = $conn->insert_id;
            header("Location: chat.php?chat_id=$chat_id");
            exit;
        } else {
            echo "Error al crear el chat: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Publicación</title>
    <link rel="shortcut icon" href="../img/logo.png" />
        <link rel="stylesheet" href="../css/detalle_publicacion.css">


    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Estilos CSS personalizados para el carrusel */
        .carousel-container {
            position: relative;
            width: 100%;
            overflow: hidden;
            border-radius: 10px;
        }
        .carousel-slide {
            display: none;
            width: 100%;
            transition: transform 0.5s ease;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 400px; /* Altura fija para el carrusel */
        }
        .carousel-slide img {
            height: 400px; /* Altura fija de imagen */
            width: auto;  /* Ancho automático para mantener la proporción */
            max-width: 100%; /* Asegurar que la imagen no se desborde del contenedor */
            object-fit: contain;
            border-radius: 10px;
        }
        .carousel-controls {
            display: none; /* Ocultar botones de control */
        }

        .carousel-control-button {
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            border: none;
            padding: 12px 18px;
            cursor: pointer;
            border-radius: 5px;
            font-size: 18px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        .carousel-control-button:hover {
            background-color: rgba(0, 0, 0, 0.9);
            transform: translateY(-1px);
        }
        .carousel-control-button:active {
            transform: translateY(0);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }
        .carousel-indicators {
            display: none; /* Ocultar indicadores */
        }
        .carousel-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.3);
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }
        .carousel-indicator.active {
            background-color: #DE9929;
            transform: scale(1.2);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        /* Estilos para la información de la publicación */
        .product-info {
            padding: 1.5rem; /* Reducido de 25px */
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Reducido de 0 6px 8px */
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .product-info:hover {
            transform: translateY(-2px); /* Reducido de -4px */
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15); /* Reducido de 0 8px 12px */
        }
        .product-title {
            font-size: 2.5rem; /* Reducido de 3rem */
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 1rem; /* Reducido de 1.5rem */
            letter-spacing: -0.02em;
        }
        .product-description {
            color: #4a5568;
            line-height: 1.6; /* Reducido de 1.8 */
            margin-bottom: 1.5rem; /* Reducido de 2rem */
            font-size: 1rem; /* Reducido de 1.1rem */
        }
        .product-price {
            font-size: 2rem; /* Reducido de 2.5rem */
            font-weight: 700;
            color: #DE9929;
            margin-bottom: 1.5rem; /* Reducido de 2rem */
            text-align: left;
        }
        .product-details-label {
            font-size: 1.2rem; /* Reducido de 1.4rem */
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.5rem; /* Reducido de 0.75rem */
            display: block;
        }
        .product-details-value {
            color: #4a5568;
            margin-bottom: 1rem; /* Reducido de 1.5rem */
            font-size: 1rem; /* Reducido de 1.1rem */
        }

        .chat-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 1rem 2rem; /* Reducido de 1.25rem 2.5rem */
            background-color: #DE9929;
            color: black;
            text-decoration: none;
            border-radius: 0.5rem; /* Reducido de 0.75rem */
            font-size: 1.2rem; /* Reducido de 1.5rem */
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            margin-top: 1.5rem; /* Reducido de 2.5rem */
            width: fit-content;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2); /* Reducido de 0 4px 6px */
        }

        .chat-button:hover {
            background-color: #c87e00;
            transform: translateY(-2px); /* Reducido de -4px */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3); /* Reducido de 0 6px 8px */
        }

        .chat-button:active {
            background-color: #b06500;
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .chat-button-icon {
            margin-right: 0.5rem; /* Reducido de 0.75rem */
            height: 1.5rem; /* Reducido de 1.75rem */
            width: 1.5rem; /* Reducido de 1.75rem */
            color: black;
        }

        /* Estilos para la sección de "Publicado por" */
        .seller-info {
            margin-top: 2rem; /* Reducido de 3rem */
            padding-top: 1.5rem; /* Reducido de 2rem */
            border-top: 1px solid #e2e8f0;
        }
        .seller-name {
            font-size: 1.6rem; /* Reducido de 1.8rem */
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.3rem; /* Reducido de 0.5rem */
        }
        .seller-link {
            color: #DE9929;
            text-decoration: none;
            transition: color 0.2s ease, transform 0.2s ease;
            font-size: 1.1rem; /* Reducido de 1.2rem */
            display: inline-flex;
            align-items: center;
            gap: 0.3rem; /* Reducido de 0.5rem */
        }
        .seller-link:hover {
            color: #c87e00;
            transform: translateX(1px); /* Reducido de 2px */
        }

        body {
            background-color: black;
            color: white;
        }

        .bg-white {
            background-color: white;
            color: black;
        }

        .text-gray-600, .text-gray-700, .text-gray-800 {
            color: #4a5568;
        }

        .border-gray-200, .border-gray-300{
            border-color: #e2e8f0;
        }
        .go-back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem; /* Reducido de 0.5rem */
            padding: 0.6rem 1.2rem; /* Reducido de 0.75rem 1.5rem */
            border-radius: 0.4rem; /* Reducido de 0.5rem */
            background-color: rgba(255, 255, 255, 0.1);
            color: #DE9929;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s ease, color 0.3s ease, transform 0.2s ease;
            margin-bottom: 1.5rem; /* Reducido de 2rem */
            border: 2px solid #DE9929;
        }

        .go-back-button:hover {
            background-color: #DE9929;
            color: black;
            transform: translateX(-1px); /* Reducido de -2px */
        }

        .go-back-button-icon {
            height: 1rem; /* Reducido de 1.25rem */
            width: 1rem; /* Reducido de 1.25rem */
            stroke-width: 2.5;
        }

        /* Nuevos estilos para el diseño de imagen a la izquierda e info a la derecha */
        .grid-container {
            display: grid;
            grid-template-columns: 1fr 1fr; /* Dos columnas de igual ancho */
            gap: 2rem; /* Espacio entre las columnas, reducido de 4rem */
            align-items: center; /* Centrar verticalmente los elementos */
        }

        .image-column {
            display: flex;
            flex-direction: column; /* Añadir para apilar imagen principal y miniaturas */
            align-items: center;
        }

        .info-column {

        }

        @media (max-width: 768px) {
            .grid-container {
                grid-template-columns: 1fr; /* Volver a una sola columna en pantallas pequeñas */
                gap: 1rem; /* Reducido de 2rem */
            }
            .image-column {
                order: 1; /* La imagen aparece primero en pantallas pequeñas */
            }
            .info-column {
                order: 2; /* La información aparece segundo */
            }
            .carousel-container {
                margin-bottom: 1rem; /* Reducido de 2rem */
            }
        }
        .carousel-container {
            margin-bottom: 0;
        }

        /* Estilos para las miniaturas */
        .thumbnail-container {
            display: flex;
            gap: 0.5rem; /* Reducido de 1rem */
            margin-top: 0.5rem; /* Reducido de 1rem */
            overflow-x: auto; /* Permitir desplazamiento horizontal si hay muchas miniaturas */
            max-width: 100%;
            padding: 0 0.5rem; /* Reducido de 0 1rem */
            overflow: hidden; /* Ocultar la barra de desplazamiento horizontal */
        }
        .thumbnail {
            width: 60px;  /* Tamaño fijo para las miniaturas, reducido de 80px */
            height: 60px; /* Tamaño fijo para las miniaturas, reducido de 80px */
            border-radius: 5px;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            object-fit: cover; /* Recortar la imagen para que se ajuste al tamaño */
        }
        .thumbnail:hover {
            transform: translateY(-1px) scale(1.05); /* Reducido de -2px y 1.1 */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2); /* Reducido de 0 4px 6px */
        }
        .thumbnail.active {
            border: 2px solid #DE9929;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.3); /* Reducido de 0 2px 4px */
        }
    </style>
</head>
<body class="bg-black text-white font-inter">
    <div class="container mx-auto p-4 lg:p-6"> <a href="javascript:history.back()" class="go-back-button">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left-circle go-back-button-icon"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="M12 8v8"/><path d="M16 12l-4-4-4 4"/></svg>
            Volver
        </a>
        <div class="bg-white rounded-lg shadow-lg overflow-hidden p-4"> <div class="grid-container">
                <div class="image-column">
                    <div class="carousel-container">
                        <?php if (empty($imagenes_galeria)): ?>
                            <div class="carousel-slide">
                                <img src="https://placehold.co/400x300/EEE/31343C" alt="No hay imágenes disponibles">
                            </div>
                        <?php else: ?>
                            <?php foreach ($imagenes_galeria as $index => $imagen_url): ?>
                                <div class="carousel-slide" data-index="<?php echo $index; ?>">
                                    <img src="<?php echo $imagen_url; ?>" alt="Imagen de la publicación">
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="carousel-controls">
                        <button class="carousel-control-button prev" aria-label="Anterior">❮</button>
                        <button class="carousel-control-button next" aria-label="Siguiente">❯</button>
                    </div>
                    <?php if (count($imagenes_galeria) > 1): ?>
                        <div class="carousel-indicators">
                            <?php for ($i = 0; $i < count($imagenes_galeria); $i++): ?>
                                <div class="carousel-indicator <?php echo $i === 0 ? 'active' : ''; ?>"></div>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                    <div class="thumbnail-container">
                        <?php if (!empty($imagenes_galeria)): ?>
                            <?php foreach ($imagenes_galeria as $index => $imagen_url): ?>
                                <img src="<?php echo $imagen_url; ?>" alt="Miniatura <?php echo $index + 1; ?>" class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>">
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="info-column">
                    <div class="product-info">
                        <h1 class="product-title"><?php echo $row['titulo']; ?></h1>
                        <p class="product-description"><?php echo $row['descripcion']; ?></p>
                        <p class="product-price"><?php echo $row['precio']; ?>€</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2"> <div>
                                <p class="product-details-label">Ubicación:</p>
                                <p class="product-details-value"><?php echo $row['ubicacion']; ?></p>
                            </div>
                            <div>
                                <p class="product-details-label">Estado:</p>
                                <p class="product-details-value"><?php echo $row['estado']; ?></p>
                            </div>
                        </div>

                        <div class="seller-info">
                            <p class="product-details-label">Vendedor:</p>
                            <a href="#" class="seller-link">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                <?php echo $row['nombre_vendedor']; ?>
                            </a>
                        </div>

                        <?php if ($sesionIniciada): ?>
                            <a href="?id=<?php echo $publicacion_id; ?>&crear_chat=1" class="chat-button">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-circle chat-button-icon"><path d="M21.17 8a2.83 2.83 0 0 1-2.6-2H4.83a2.83 2.83 0 0 1-2.6 2"/><path d="M2.37 15.33a2.83 2.83 0 0 1 2.6 2H19.17a2.83 2.83 0 0 1 2.6-2z"/><path d="M12 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
                                Contactar al vendedor
                            </a>
                        <?php else: ?>
                            <a href="InicioSesion.php" class="chat-button">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-in chat-button-icon"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5"/><path d="M13 9h-7a1 1 0 0 0-1 1v6a1 1 0 0 0 1 1h7"/></svg>
                                Iniciar Sesión para contactar
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // JavaScript para el carrusel
        const carouselContainer = document.querySelector('.carousel-container');
        const carouselSlides = document.querySelectorAll('.carousel-slide');
        const carouselControls = document.querySelectorAll('.carousel-control-button');
        const carouselIndicators = document.querySelectorAll('.carousel-indicator');
        const thumbnailContainer = document.querySelector('.thumbnail-container');
        const thumbnails = document.querySelectorAll('.thumbnail');

        let slideIndex = 0;

        function showSlide(index) {
            carouselSlides.forEach(slide => slide.style.display = 'none');
            if (carouselIndicators) {
                carouselIndicators.forEach(indicator => indicator.classList.remove('active'));
            }
             if (thumbnails) {
                thumbnails.forEach(thumbnail => thumbnail.classList.remove('active'));
            }
            carouselSlides[index].style.display = 'flex';
            if (carouselIndicators) {
                carouselIndicators[index].classList.add('active');
            }
            if (thumbnails) {
                 thumbnails[index].classList.add('active');
            }
        }

        function changeSlide(index) {
            slideIndex = index;
            showSlide(slideIndex);
        }

        if (thumbnails) {
            thumbnails.forEach((thumbnail, index) => {
                thumbnail.addEventListener('click', () => changeSlide(index));
            });
        }

        showSlide(slideIndex);

        // Opcional: Para que el carrusel funcione correctamente si solo hay una imagen
        if (carouselSlides.length <= 1) {
            carouselControls[0].style.display = 'none';
            carouselControls[1].style.display = 'none';
            if (carouselIndicators) {
                carouselIndicators.forEach(indicator => indicator.style.display = 'none');
            }
             if (thumbnails) {
                thumbnails.forEach(thumbnail => thumbnail.style.display = 'none');
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>