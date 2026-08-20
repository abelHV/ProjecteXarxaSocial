<?php
session_start();

// Incluir el archivo de conexión a la base de datos
include '../php/db.php';

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // No autorizado
    echo "Usuario no autenticado.";
    exit;
}

$usuario_id = $_SESSION['user_id'];

// Obtener los datos del mensaje
$chat_id = $_POST['chat_id'];
$mensaje = $_POST['mensaje'];

// Insertar el mensaje en la base de datos
$sql_insertar_mensaje = "INSERT INTO mensajes (chat_id, usuario_id, contenido) VALUES ($chat_id, $usuario_id, '$mensaje')";
if ($conn->query($sql_insertar_mensaje)) {
    http_response_code(200); // OK
    echo "Mensaje enviado correctamente.";
} else {
    http_response_code(500); // Error interno del servidor
    echo "Error al enviar el mensaje: " . $conn->error;
}

$conn->close();
?>