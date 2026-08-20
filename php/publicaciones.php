<?php
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Debes iniciar sesión para subir una publicación.";
    header("Location: ../html/InicioSesion.html");
    exit;
}

// Incluir el archivo de conexión a la base de datos
include 'db.php';

// Definir el límite máximo de imágenes
$max_images = 12;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titulo = $_POST["titulo"] ?? '';
    $descripcion = $_POST["descripcion"] ?? '';
    $categoria = $_POST["categoria"] ?? '';
    $precio = floatval($_POST["precio"] ?? 0.00); 
    
    $ubicacion_texto = $_POST["ubicacion_texto"] ?? ''; 

    $latitud = $_POST["latitud"] ?? null; 
    $longitud = $_POST["longitud"] ?? null;
    
    $estado = $_POST["estado"] ?? '';
    $usuario_id = $_SESSION['user_id'];

    // Validaciones básicas de campos obligatorios
    if (empty($titulo) || empty($descripcion) || empty($categoria) || empty($ubicacion_texto) || empty($estado) || $precio <= 0 || is_null($latitud) || is_null($longitud)) {
        $_SESSION['error_message'] = "Por favor, completa todos los campos obligatorios (Título, Descripción, Categoría, Ubicación, Estado, Precio). El precio debe ser mayor que 0 y la ubicación debe ser válida.";
        header("Location: ../html/publicaciones.php"); 
        exit();
    }

    // Iniciar transacción para asegurar la integridad de los datos
    $conn->begin_transaction();
    $uploadOk = 1;
    $error_message = "";

    // Consulta SQL para incluir latitud y longitud, según tu tabla actual
    $sql_publicacion = "INSERT INTO publicacions (usuario_id, titulo, descripcion, categoria, precio, ubicacion, estado, latitud, longitud, fecha_creacion, fecha_actualizacion)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

    $stmt_publicacion = $conn->prepare($sql_publicacion);

    if ($stmt_publicacion === false) {
        error_log("Error al preparar la consulta de publicación: " . $conn->error);
        $_SESSION['error_message'] = "Error interno del servidor al procesar la publicación.";
        $conn->rollback();
        header("Location: ../html/publicaciones.php");
        exit();
    }

    // --- CORRECCIÓN CLAVE AQUÍ: La cadena de tipos 'isssdsdds' ---
    // 'i'  -> usuario_id (int)
    // 's'  -> titulo (string)
    // 's'  -> descripcion (string)
    // 's'  -> categoria (string)
    // 'd'  -> precio (double/float)
    // 's'  -> ubicacion_texto (string)
    // 's'  -> estado (string) - Aunque es ENUM, MySQLi lo trata como string en bind_param
    // 'd'  -> latitud (double/float)
    // 'd'  -> longitud (double/float)
    // Total de 9 parámetros y 9 tipos.
    $stmt_publicacion->bind_param("isssdssds",
        $usuario_id,
        $titulo,
        $descripcion,
        $categoria,
        $precio,
        $ubicacion_texto,
        $estado,
        $latitud,
        $longitud
    );

    if ($stmt_publicacion->execute()) {
        $publicacion_id = $conn->insert_id;
        $stmt_publicacion->close();

        $target_dir = "../uploads/";

        if (!file_exists($target_dir) && !is_dir($target_dir)) {
            if (!mkdir($target_dir, 0777, true)) {
                $error_message = "Error: No se pudo crear el directorio de subida de imágenes.";
                $uploadOk = 0;
            }
        }

        if ($uploadOk && isset($_FILES["imagenes"]) && is_array($_FILES["imagenes"]["name"])) {
            $total_images = count($_FILES["imagenes"]["name"]);

            if ($total_images > $max_images) {
                $error_message = "Se ha excedido el límite de " . $max_images . " imágenes.";
                $uploadOk = 0;
            } else {
                $sql_galeria = "INSERT INTO galeria_fotos (publicacion_id, imagen) VALUES (?, ?)"; 
                $stmt_galeria = $conn->prepare($sql_galeria);

                if ($stmt_galeria === false) {
                    error_log("Error al preparar la consulta de galería de fotos: " . $conn->error);
                    $error_message = "Error interno del servidor al procesar las imágenes.";
                    $uploadOk = 0;
                } else {
                    for ($i = 0; $i < $total_images; $i++) {
                        if ($_FILES["imagenes"]["error"][$i] === UPLOAD_ERR_OK) {
                            $file_name = $_FILES["imagenes"]["name"][$i];
                            $file_tmp_name = $_FILES["imagenes"]["tmp_name"][$i];
                            $file_size = $_FILES["imagenes"]["size"][$i];
                            $imageFileType = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                            $new_file_name = uniqid('img_', true) . '.' . $imageFileType;
                            $target_file_path = $target_dir . $new_file_name;

                            $check = getimagesize($file_tmp_name);
                            if ($check === false) {
                                $error_message = "Uno de los archivos subidos no es una imagen válida.";
                                $uploadOk = 0;
                                break;
                            }

                            if ($file_size > 500000) { // 500 KB
                                $error_message = "Uno de los archivos es demasiado grande (máx 500KB).";
                                $uploadOk = 0;
                                break;
                            }

                            $allowed_types = ["jpg", "png", "jpeg", "gif"];
                            if (!in_array($imageFileType, $allowed_types)) {
                                $error_message = "Solo se permiten archivos JPG, JPEG, PNG y GIF.";
                                $uploadOk = 0;
                                break;
                            }

                            if (move_uploaded_file($file_tmp_name, $target_file_path)) {
                                // Save the path as "../uploads/filename.ext"
                                $image_path_for_db = "../uploads/" . $new_file_name;
                                $stmt_galeria->bind_param("is", $publicacion_id, $image_path_for_db); 
                                if (!$stmt_galeria->execute()) {
                                    $error_message = "Error al guardar la ruta de la imagen en la galería: " . $stmt_galeria->error;
                                    $uploadOk = 0;
                                    break;
                                }
                            } else {
                                $error_message = "Error al subir uno de los archivos al servidor.";
                                $uploadOk = 0;
                                break;
                            }
                        } else if ($_FILES["imagenes"]["error"][$i] !== UPLOAD_ERR_NO_FILE) {
                            $error_message = "Error en la subida del archivo " . htmlspecialchars($file_name) . " (Código: " . $_FILES["imagenes"]["error"][$i] . ").";
                            $uploadOk = 0;
                            break;
                        }
                    }
                    $stmt_galeria->close(); 
                }
            }
        } 

        if ($uploadOk) {
            $conn->commit();
            $_SESSION['success_message'] = "Publicación subida con éxito.";
            header("Location: ../index.php"); 
            exit();
        } else {
            $conn->rollback(); 
            $_SESSION['error_message'] = "Error al subir la publicación: " . $error_message;
            header("Location: ../html/publicaciones.php"); 
            exit();
        }

    } else {
        $conn->rollback(); 
        error_log("Error al guardar la publicación principal: " . $stmt_publicacion->error);
        $_SESSION['error_message'] = "Error al guardar la publicación principal: " . $stmt_publicacion->error;
        header("Location: ../html/publicaciones.php"); 
        exit();
    }
}

$conn->close();
?>