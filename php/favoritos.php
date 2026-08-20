<?php
session_start();
include 'db.php'; // Incluye tu archivo de conexión a la base de datos

if (!isset($_SESSION['user_id'])) {
    echo "Usuario no autenticado.";
    exit;
}

$usuario_id = $_SESSION['user_id'];

if (isset($_POST['accion']) && isset($_POST['publicacion_id'])) {
    $accion = $_POST['accion'];
    $publicacion_id = $_POST['publicacion_id'];

    if ($accion === 'agregar') {
        // Verificar si ya existe en favoritos para evitar duplicados
        $check_sql = "SELECT * FROM favoritos WHERE usuario_id = $usuario_id AND publicacion_id = $publicacion_id";
        $check_result = $conn->query($check_sql);
        if ($check_result->num_rows === 0) {
            $insert_sql = "INSERT INTO favoritos (usuario_id, publicacion_id, fecha_agregado) VALUES ($usuario_id, $publicacion_id, NOW())";
            if ($conn->query($insert_sql) === TRUE) {
                echo "Anuncio agregado a favoritos.";
            } else {
                echo "Error al agregar a favoritos: " . $conn->error;
            }
        } else {
            echo "El anuncio ya está en tus favoritos.";
        }
    } elseif ($accion === 'eliminar') {
        $delete_sql = "DELETE FROM favoritos WHERE usuario_id = $usuario_id AND publicacion_id = $publicacion_id";
        if ($conn->query($delete_sql) === TRUE) {
            echo "Anuncio eliminado de favoritos.";
        } else {
            echo "Error al eliminar de favoritos: " . $conn->error;
        }
    } else {
        echo "Acción no válida.";
    }
} elseif (isset($_GET['accion']) && $_GET['accion'] === 'obtener_favoritos') {
    // Esta parte es opcional y sirve para cargar el estado inicial
    $favoritos = [];
    $select_sql = "SELECT publicacion_id FROM favoritos WHERE usuario_id = $usuario_id";
    $result = $conn->query($select_sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $favoritos[] = $row['publicacion_id'];
        }
    }
    header('Content-Type: application/json');
    echo json_encode($favoritos);
}

$conn->close();
?>