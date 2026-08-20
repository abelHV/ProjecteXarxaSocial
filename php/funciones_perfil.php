<?php

define('DIRECTORIO_SUBIDA', '../uploads/');

function obtenerDatosUsuario($user_id) {
    $conn = new mysqli("localhost", "root", "", "cardcapture");
    if ($conn->connect_error) {
        return false;
    }

    $sql = "SELECT nom, cognom, dataNaixement, localitzacio, descripcio, imagen_perfil FROM Usuari WHERE id_user = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return false;
    }
}

function actualizarDatosUsuario($user_id, $nombre, $apellido, $fecha_nacimiento, $localizacion, $descripcion) {
    $conn = new mysqli("localhost", "root", "", "cardcapture");
    if ($conn->connect_error) {
        return "Error de conexión: " . $conn->connect_error;
    }

    $sql = "UPDATE Usuari SET nom = ?, cognom = ?, dataNaixement = ?, localitzacio = ?, descripcio = ? WHERE id_user = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $nombre, $apellido, $fecha_nacimiento, $localizacion, $descripcion, $user_id);

    if ($stmt->execute()) {
        return true;
    } else {
        return "Error al actualizar el perfil: " . $stmt->error;
    }
}

function actualizarFotoPerfil($user_id, $nueva_imagen) {
    $conn = new mysqli("localhost", "root", "", "cardcapture");
    if ($conn->connect_error) {
        return "Error de conexión: " . $conn->connect_error;
    }

    if ($nueva_imagen['error'] !== UPLOAD_ERR_OK) {
        return "Error al subir la imagen.";
    }

    $nombre_archivo = uniqid() . "_" . $nueva_imagen['name'];
    $ruta_destino = DIRECTORIO_SUBIDA . $nombre_archivo;
    $ruta_destino_db = 'uploads/' . $nombre_archivo;

    if (!file_exists(DIRECTORIO_SUBIDA)) {
        mkdir(DIRECTORIO_SUBIDA, 0777, true);
    }

    if (!move_uploaded_file($nueva_imagen['tmp_name'], $ruta_destino)) {
        return "Error al guardar la imagen.";
    }

    $sql = "UPDATE Usuari SET imagen_perfil = ? WHERE id_user = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $ruta_destino_db, $user_id);

    if ($stmt->execute()) {
        return true;
    } else {
        return "Error al actualizar la base de datos: " . $stmt->error;
    }
}
?>