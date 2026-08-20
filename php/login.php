<?php
// login.php
session_start(); // Inicia la sesión para usar las variables $_SESSION
require 'db.php'; // Asegúrate de que esta ruta sea correcta

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $sql = "SELECT id_user, passHash, nom, cognom FROM Usuari WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user["passHash"])) {
        // Inicio de sesión exitoso
        $_SESSION["user_id"] = $user["id_user"];
        $_SESSION["username"] = $username;

        // Almacena el mensaje de éxito en la sesión para el popup en index.php
        $_SESSION['flash_status'] = 'success';
        $_SESSION['flash_message'] = 'Sessió iniciada!';

        // Redirige directamente a la página principal (index.php)
        header('Location: ../index.php');
        exit(); // Es crucial llamar a exit() después de header()
    } else {
        // Inicio de sesión fallido
        // Almacena el mensaje de error en la sesión para el popup en InicioSesion.php
        $_SESSION['flash_status'] = 'error';
        $_SESSION['flash_message'] = 'Error: Usuari o contrasenya incorrectes.';

        // Redirige de vuelta a la página de inicio de sesión (que ahora es PHP)
        header('Location: ../html/InicioSesion.php'); // <-- IMPORTANTE: Asegúrate de que esta ruta sea correcta después de renombrar
        exit(); // Es crucial llamar a exit() después de header()
    }
}
?>